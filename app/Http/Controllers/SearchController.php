<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SearchController extends Controller
{
    public function search(Request $req)
    {
        // 1) Validación
        $req->validate([
            'query'     => 'required|string|min:2|max:200',
            'categoria' => 'nullable|string|max:80',
            'barrio'    => 'nullable|string|max:120',
            'k'         => 'nullable|integer|min:1|max:50',
            'user_lat'  => 'nullable|numeric',
            'user_lon'  => 'nullable|numeric',
            'use_ia'    => 'nullable|boolean'
        ]);

        $q       = trim($req->input('query'));
        $catIn   = $req->input('categoria') ?: null;
        $barIn   = $req->input('barrio') ?: null;
        $k       = $req->input('k', 12);
        $userLat = $req->input('user_lat');
        $userLon = $req->input('user_lon');
        $useIA   = $req->boolean('use_ia', true); // toggle para comparar Con IA / Sin IA

        // 2) Expandir consulta y detectar categoría por palabras clave
        [$qExpanded, $catDetected] = $this->expandQuery($q);
        $categoria = $catIn ?: $catDetected;

        // 3) Estrategia en cascada para traer candidatos
        $strategies = [
            ['name' => 'FULLTEXT_strict',        'categoria' => $categoria, 'barrio' => $barIn, 'mode' => 'FT'],
            ['name' => 'FULLTEXT_no_barrio',     'categoria' => $categoria, 'barrio' => null,   'mode' => 'FT'],
            ['name' => 'FULLTEXT_no_categoria',  'categoria' => null,       'barrio' => $barIn, 'mode' => 'FT'],
            ['name' => 'FULLTEXT_sin_filtros',   'categoria' => null,       'barrio' => null,   'mode' => 'FT'],
            ['name' => 'LIKE_sin_filtros',       'categoria' => null,       'barrio' => null,   'mode' => 'LIKE'],
        ];

        $items = [];
        $used  = null;

        foreach ($strategies as $st) {
            $items = $this->fetchCandidates($qExpanded, $st['categoria'], $st['barrio'], max($k, 8), $st['mode']);
            if (!empty($items)) { $used = $st['name']; break; }
        }

        if (!$items) {
            return response()->json([
                'items' => [],
                'meta'  => [
                    'query' => $q,
                    'query_expanded' => $qExpanded,
                    'categoria_detectada' => $catDetected,
                    'estrategia' => 'sin_resultados',
                    'info' => 'Sin coincidencias. Cambia palabras o quita filtros.'
                ]
            ], 200);
        }

        // ===== 4) Re-ranking con IA (si use_ia=true y hay key) =====
        $docs = [];
        foreach ($items as $it) {
            // Texto compacto de cada candidato (recorta para ahorrar tokens)
            $txt = $it->titulo.' — '.$it->descripcion.' ('.$it->barrio.')';
            $docs[] = mb_substr($txt, 0, 800, 'UTF-8');
        }

        $iaScores = [];
        $iaMeta   = ['proveedor' => null, 'top_n' => null, 'ok' => false, 'warning' => null];

        if ($useIA) {
            try {
                [$iaScores, $iaMeta] = $this->callReranker($q, $docs, min($k, count($docs)));
            } catch (\Throwable $e) {
                $iaMeta['warning'] = 'Fallo IA: '.$e->getMessage();
            }
        } else {
            $iaMeta['warning'] = 'IA desactivada por parámetro use_ia=false';
        }

        // ===== 5) Mezcla de puntajes y ranking final =====
        $hasGeo = is_numeric($userLat) && is_numeric($userLon);

        // Si no hay IA, usamos ft_score como respaldo textual
        $baseMax = 0.0001;
        foreach ($items as $it) { $baseMax = max($baseMax, (float)($it->ft_score ?? 0)); }

        $ranked = [];
        $maxIa = 0.0001;
        if (!empty($iaScores)) foreach ($iaScores as $sc) { $maxIa = max($maxIa, (float)$sc); }

        foreach ($items as $idx => $it) {
            // 1) texto: IA si hay, si no ft_score normalizado
            if (!empty($iaScores)) {
                $ia = $maxIa > 0 ? ($iaScores[$idx] / $maxIa) : 0.0;   // 0..1
            } else {
                $ia = ((float)($it->ft_score ?? 0)) / $baseMax;        // respaldo
            }

            // 2) señales de negocio
            $rating = min(max(((float)$it->rating_promedio)/5.0, 0), 1);
            $prox = 0.5;
            if ($hasGeo && $it->lat !== null && $it->lon !== null) {
                $dKm  = $this->haversine($userLat, $userLon, (float)$it->lat, (float)$it->lon);
                $prox = max(0, min(1, 1 - ($dKm / 25)));
            }

            // 3) mezcla final
            $final = 0.70*$ia + 0.20*$prox + 0.10*$rating;

            $ranked[] = [
                'final_score' => round($final, 3),
                'ia_score'    => round($ia, 3),
                'prox'        => round($prox, 3),
                'rating_n'    => round($rating, 3),
                'item'        => $it
            ];
        }

        usort($ranked, fn($a,$b)=> $b['final_score'] <=> $a['final_score']);

        $out = array_map(function($r){
            $c = $r['item'];
            return [
                'id'          => $c->id,
                'titulo'      => $c->titulo,
                'descripcion' => $c->descripcion,
                'categoria'   => $c->categoria,
                'proveedor'   => $c->proveedor,
                'barrio'      => $c->barrio,
                'rating'      => (float)$c->rating_promedio,
                'ia_score'    => $r['ia_score'],
                'final_score' => $r['final_score'],
                'badges'      => ($r['final_score']>0.8) ? ['Recomendado'] : [],
            ];
        }, $ranked);

        return response()->json([
            'items' => array_slice($out, 0, $k),
            'meta'  => [
                'query' => $q,
                'query_expanded' => $qExpanded,
                'categoria_detectada' => $catDetected,
                'estrategia' => $used,
                'ia' => [
                    'proveedor' => $iaMeta['proveedor'],
                    'top_n' => $iaMeta['top_n'],
                    'ok' => $iaMeta['ok'],
                    'warning' => $iaMeta['warning'],
                    'candidatos_enviados_a_ia' => count($docs),
                ],
                'k' => $k
            ]
        ]);
    }

    // -------- Helpers --------

    private function expandQuery(string $q): array
    {
        $ql = mb_strtolower($q, 'UTF-8');
        $catMap = [
            'seguridad' => 'Seguridad', 'cctv' => 'Seguridad', 'cámara' => 'Seguridad',
            'camaras' => 'Seguridad', 'cámaras' => 'Seguridad', 'videovigilancia' => 'Seguridad',
            'plomería' => 'Plomería', 'plomeria' => 'Plomería', 'calefón' => 'Plomería', 'calefon' => 'Plomería',
            'grifería' => 'Plomería', 'tubería' => 'Plomería',
            'electricidad' => 'Electricidad', 'eléctrico' => 'Electricidad', 'tablero' => 'Electricidad',
            'tomacorriente' => 'Electricidad',
            'cerrajero' => 'Cerrajería', 'cerrajería' => 'Cerrajería', 'cerradura' => 'Cerrajería', 'llaves' => 'Cerrajería',
            'wifi' => 'Redes', 'internet' => 'Redes', 'router' => 'Redes', 'redes' => 'Redes',
            'pintura' => 'Pintura', 'estuco' => 'Pintura', 'impermeabilización' => 'Pintura',
        ];

        $detected = null;
        foreach ($catMap as $word => $cat) {
            if (str_contains($ql, $word)) { $detected = $cat; break; }
        }

        $expanded = $ql;
        if (str_contains($ql, 'cámara') || str_contains($ql, 'camaras') || str_contains($ql, 'cámaras') || str_contains($ql, 'cctv')) {
            $expanded .= ' cctv seguridad videovigilancia cámaras cámara';
        }
        if (str_contains($ql, 'wifi') || str_contains($ql, 'internet') || str_contains($ql, 'router') || str_contains($ql, 'red')) {
            $expanded .= ' redes internet router wifi';
        }

        $expanded = trim(preg_replace('/\s+/', ' ', $expanded));
        return [$expanded, $detected];
    }

    private function fetchCandidates(string $qExpanded, ?string $categoria, ?string $barrio, int $k, string $mode)
    {
        $params = [];
        $where  = " s.activo = 1 ";
        if ($categoria) { $where .= " AND s.categoria = ? ";  $params[] = $categoria; }
        if ($barrio)    { $where .= " AND p.barrio = ? ";     $params[] = $barrio; }

        if ($mode === 'FT') {
            $sql = "
                SELECT
                    s.id, s.titulo, s.descripcion, s.categoria,
                    p.nombre AS proveedor, p.barrio, p.lat, p.lon, p.rating_promedio,
                    MATCH (s.titulo, s.descripcion) AGAINST (? IN NATURAL LANGUAGE MODE) AS ft_score
                FROM services s
                JOIN providers p ON p.id = s.provider_id
                WHERE {$where}
                  AND MATCH (s.titulo, s.descripcion) AGAINST (? IN NATURAL LANGUAGE MODE)
                ORDER BY ft_score DESC, p.rating_promedio DESC
                LIMIT ".max($k, 8)."
            ";
            try {
                return DB::select($sql, array_merge($params, [$qExpanded, $qExpanded]));
            } catch (\Throwable $e) {
                return [];
            }
        } else {
            $sql = "
                SELECT
                    s.id, s.titulo, s.descripcion, s.categoria,
                    p.nombre AS proveedor, p.barrio, p.lat, p.lon, p.rating_promedio,
                    ((s.titulo LIKE CONCAT('%', ?, '%')) + (s.descripcion LIKE CONCAT('%', ?, '%'))) AS ft_score
                FROM services s
                JOIN providers p ON p.id = s.provider_id
                WHERE {$where}
                  AND (s.titulo LIKE CONCAT('%', ?, '%') OR s.descripcion LIKE CONCAT('%', ?, '%'))
                ORDER BY ft_score DESC, p.rating_promedio DESC
                LIMIT ".max($k, 8)."
            ";
            return DB::select($sql, array_merge($params, [$qExpanded, $qExpanded, $qExpanded, $qExpanded]));
        }
    }

    private function haversine($lat1,$lon1,$lat2,$lon2) {
        $R = 6371; // km
        $dLat = deg2rad($lat2-$lat1);
        $dLon = deg2rad($lon2-$lon1);
        $a = sin($dLat/2)**2 + cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dLon/2)**2;
        return 2*$R*asin(min(1, sqrt($a)));
    }

    // === IA RERANK ===
    private function callReranker(string $query, array $documents, int $topN): array
    {
        $prov  = config('services.rerank.provider');
        $url   = config('services.rerank.url');
        $key   = config('services.rerank.key');
        $model = config('services.rerank.model');

        if (!$url || !$key) {
            return [[], ['proveedor'=>$prov, 'top_n'=>$topN, 'ok'=>false, 'warning'=>'RERANK_URL/RERANK_KEY no configurados']];
        }

        $scores = [];
        $meta   = ['proveedor'=>$prov, 'top_n'=>$topN, 'ok'=>false, 'warning'=>null];

        // usa el mismo cacert de /api/ia-test si existe
        $verifyPath = storage_path('certs/cacert.pem');
        $http = Http::withToken($key)->timeout(12);
        if (file_exists($verifyPath)) {
            $http = $http->withOptions(['verify' => $verifyPath]);
        }
        // $http = $http->withoutVerifying(); // SOLO si hubiera problemas locales

        if ($prov === 'cohere') {
            $payload = [
                'model' => $model ?: 'rerank-multilingual-v3.0',
                'query' => $query,
                'documents' => $documents,
                'top_n' => $topN,
                'return_documents' => false,
            ];
            $resp = $http->post($url, $payload);
            if ($resp->ok()) {
                $arr = $resp->json()['results'] ?? $resp->json()['data'] ?? [];
                foreach ($arr as $r) {
                    $idx = $r['index'] ?? null;
                    if ($idx !== null) {
                        $scores[(int)$idx] = (float)($r['relevance_score'] ?? $r['score'] ?? 0.0);
                    }
                }
                $meta['ok'] = !empty($scores);
                return [$scores, $meta];
            }
            $meta['warning'] = 'HTTP '.$resp->status().' en Cohere';
            return [[], $meta];
        }

        if ($prov === 'jina') {
            $payload = [
                'model' => $model ?: 'jina-reranker-v2-base',
                'query' => $query,
                'documents' => $documents,
                'top_n' => $topN,
            ];
            $resp = $http->post($url, $payload);
            if ($resp->ok()) {
                $arr = $resp->json()['results'] ?? $resp->json()['data'] ?? [];
                foreach ($arr as $r) {
                    $idx = $r['index'] ?? null;
                    if ($idx !== null) {
                        $scores[(int)$idx] = (float)($r['relevance_score'] ?? $r['score'] ?? 0.0);
                    }
                }
                $meta['ok'] = !empty($scores);
                return [$scores, $meta];
            }
            $meta['warning'] = 'HTTP '.$resp->status().' en Jina';
            return [[], $meta];
        }

        return [[], ['proveedor'=>$prov, 'top_n'=>$topN, 'ok'=>false, 'warning'=>'Proveedor no soportado']];
    }
}
