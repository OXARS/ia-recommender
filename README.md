# Buscador de Servicios con IA (Re-ranking como servicio)

Web en Laravel + MySQL. Recupera candidatos con FULLTEXT y re-ranquea con IA (Cohere/Jina). Combina IA + proximidad + rating.

## Requisitos
- PHP 8.2+, Composer
- MySQL 8+
- (Windows) Tener `storage/certs/cacert.pem` y en `php.ini` las rutas `curl.cainfo` y `openssl.cafile`.

## Instalación
```bash
composer install
cp .env.example .env   # (en Windows puedes copiar manual)
php artisan key:generate
# Configura DB y IA en .env (RERANK_*)

php artisan migrate --seed
php artisan serve --port=8001

RERANK_PROVIDER=cohere
RERANK_URL=https://api.cohere.ai/v1/rerank
RERANK_KEY=TU_API_KEY
RERANK_MODEL=rerank-multilingual-v3.0

Endpoints

GET / (UI)

GET /api/ping (health simple)

GET /api/ia-test (diagnóstico IA)

POST /api/search (búsqueda)

Body: query (req), categoria?, barrio?, k?, user_lat?, user_lon?, use_ia?

Lógica

Candidatos: FULLTEXT (fallback LIKE) + relajación de filtros.

IA Rerank sobre top-k.

Score final: 0.70*IA + 0.20*prox + 0.10*rating.

Uso académico.

