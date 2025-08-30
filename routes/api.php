<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;   // <-- IMPORTANTE
use App\Models\Service;
use App\Http\Controllers\SearchController;

// Debug de últimos servicios (lo que ya tenías)
Route::get('/debug/services', function () {
    return Service::with('provider')->latest()->limit(10)->get();
});

// --- IA: prueba rápida de re-ranking (diagnóstico) ---
Route::get('/ia-test', function () {
    $prov  = config('services.rerank.provider');
    $url   = config('services.rerank.url');
    $key   = config('services.rerank.key');
    $model = config('services.rerank.model');

    $query = 'instalar cámaras de seguridad en casa';
    $docs  = [
        'CCTV Hikvision instalación y DVR con app móvil',
        'Pintura interior/exterior y estuco profesional',
        'Limpieza de tanques de agua residencial',
    ];

    if (!$url || !$key) {
        return response()->json(['ok'=>false,'error'=>'Falta RERANK_URL o RERANK_KEY en .env'], 500);
    }

    if ($prov === 'cohere') {
        $payload = [
            'model'             => $model ?: 'rerank-multilingual-v3.0',
            'query'             => $query,
            'documents'         => $docs,
            'top_n'             => 3,
            'return_documents'  => false,
        ];
    } else { // 'jina' u otros
        $payload = [
            'model'     => $model ?: 'jina-reranker-v2-base',
            'query'     => $query,
            'documents' => $docs,
            'top_n'     => 3,
        ];
    }

    $resp = Http::withToken($key)->timeout(12)->post($url, $payload);

    return response()->json([
        'provider' => $prov,
        'status'   => $resp->status(),
        'raw'      => $resp->json(),
    ]);
});

// Rutas de búsqueda
Route::post('/search', [SearchController::class, 'search']);
Route::get('/search',  [SearchController::class, 'search']); // opcional
Route::get('/ping', fn() => response()->json(['ok'=>true, 'app'=>config('app.name')]));
Route::get('/health', fn() => response()->json(['ok'=>true, 'time'=>now()->toISOString()]));
