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

- Demo estática del entregable 3: http://127.0.0.1:8001/demo.html