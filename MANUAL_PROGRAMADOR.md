# Manual del Programador — Buscador de Servicios con IA

> **Stack:** Laravel 12 · PHP 8.2+ · MySQL 8 · Re-ranking IA (Cohere/Jina)

---

## 1) Descripción técnica
Aplicación **Laravel 12 + MySQL 8**.

**Flujo:**
1. **Recuperación de candidatos:** consulta **FULLTEXT** (o **LIKE** como fallback) sobre `services.titulo, services.descripcion`, con **cascada de filtros** (categoría/barrio).
2. **Re-ranking IA (Cohere):** se envía el **top-k** de candidatos; la IA devuelve **relevancias** por índice.
3. **Puntuación final:** `final = 0.70*ia + 0.20*prox + 0.10*rating`.
4. **Fallback robusto:** si IA falla (timeout/401/429), se usa **puntaje textual normalizado**.

---

## 2) Requisitos y entorno
- PHP **8.2+**, Composer, MySQL **8+**.
- **Windows (solo si ocurre cURL 60/SSL):**
  - Descargar `cacert.pem` y ubicarlo en `storage/certs/cacert.pem`.
  - En `php.ini` establecer:

```ini
curl.cainfo="C:\ruta\al\proyecto\storage\certs\cacert.pem"
openssl.cafile="C:\ruta\al\proyecto\storage\certs\cacert.pem"
3) Instalación
bash
Copiar código
composer install
cp .env.example .env
php artisan key:generate

# Configurar DB y RERANK_* en .env
php artisan migrate --seed

php artisan serve --port=8001
# UI:   http://127.0.0.1:8001/
# Demo: http://127.0.0.1:8001/demo.html
.env (ejemplos)
Base de datos

dotenv
Copiar código
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ia_local
DB_USERNAME=root
DB_PASSWORD=
Re-ranking (Cohere)

dotenv
Copiar código
RERANK_PROVIDER=cohere
RERANK_URL=https://api.cohere.ai/v1/rerank
RERANK_KEY=TU_API_KEY
RERANK_MODEL=rerank-multilingual-v3.0
🔒 Importante: No subir .env ni llaves al repositorio.

4) Estructura relevante
bash
Copiar código
app/
  Http/Controllers/SearchController.php   # lógica principal (búsqueda, IA, scoring)
database/
  migrations/                             # esquema de BD
  seeders/                                # datos de ejemplo
public/
  demo.html                               # demo estática (entregable 3)
  demo.js
resources/views/app.blade.php             # UI principal
routes/api.php                            # rutas REST
5) Esquema de Base de Datos (mínimo)
Tabla providers

id (PK)

nombre (varchar)

barrio (varchar)

lat (decimal, nullable)

lon (decimal, nullable)

rating_promedio (decimal)

created_at, updated_at

Tabla services

id (PK)

provider_id (FK → providers.id)

categoria (varchar)

titulo (varchar)

descripcion (text)

precio_desde (decimal, nullable)

activo (tinyint)

created_at, updated_at

Índices recomendados

sql
Copiar código
ALTER TABLE services  ADD FULLTEXT ft_txt (titulo, descripcion);
CREATE INDEX idx_services_categoria ON services (categoria);
CREATE INDEX idx_providers_barrio  ON providers (barrio);
6) API Contract
POST /api/search
Body JSON

json
Copiar código
{
  "query": "instalar cámaras",
  "categoria": null,
  "barrio": null,
  "k": 8,
  "user_lat": null,
  "user_lon": null,
  "use_ia": true
}
Respuesta (ejemplo)

json
Copiar código
{
  "items": [
    {
      "id": 19,
      "titulo": "Seguridad - Seguridad Hogar Plus",
      "descripcion": "Instalación de cámaras Hikvision/DAHUA, cableado, DVR/NVR, app móvil.",
      "categoria": "Seguridad",
      "proveedor": "Seguridad Hogar Plus",
      "barrio": "San Rafael",
      "rating": 4.2,
      "ia_score": 1.0,
      "final_score": 0.884,
      "badges": ["Recomendado"]
    }
  ],
  "meta": {
    "query": "instalar cámaras",
    "estrategia": "FULLTEXT_*",
    "ia": { "proveedor": "cohere", "ok": true, "candidatos_enviados_a_ia": 6 }
  }
}
Códigos

200 OK (con o sin IA)

422 Validación (falta query, etc.)

5xx Excepciones (consultar logs)

GET /api/ia-test
Llama al endpoint de re-ranking con un set fijo de pruebas.
Devuelve status, proveedor y resultados de ejemplo.

GET /api/health
Devuelve:

json
Copiar código
{ "ok": true, "app": "...", "time": "..." }
7) Lógica y scoring (detallado)
7.1 Recuperación de candidatos (cascada)
FULLTEXT con categoría + barrio.

FULLTEXT sin barrio.

FULLTEXT sin categoría.

FULLTEXT sin filtros.

LIKE sin filtros (solo si FULLTEXT no devuelve nada).

Orden por ft_score DESC, rating_promedio DESC. Top-K configurable.

7.2 Envío a IA (Cohere Rerank)
Entrada: arreglo de textos candidatos (título + descripción) y query.

Salida: índices con relevance_score.

Normalización: [0..1] asignando 1 al mejor candidato y descendiendo al resto.

7.3 Proximidad (Haversine)
Distancia entre (user_lat, user_lon) y (lat, lon) del proveedor.

Normalización: prox = max(0, 1 - (dist_km / 25)) → 0 a 25 km aporta de 1 a 0.

7.4 Rating normalizado
rating_n = rating_promedio / 5 → rango [0..1].

7.5 Mezcla final
text
Copiar código
final = 0.70 * ia_norm
      + 0.20 * prox
      + 0.10 * rating_n
Empates: desempate por rating_promedio DESC.

7.6 Fallback
Si IA falla (timeout/401/429), ia_norm se reemplaza por el score textual relativo y se procede igual.

8) Seguridad y buenas prácticas
🚫 Nunca subir .env ni llaves al repositorio.

✅ Validar todas las entradas: query min 2, k 1..50, lat/lon numéricos.

💸 Limitar top-k para controlar costos y latencia de la IA.

🧾 Registrar errores y tiempos de respuesta (HTTP Client + logs Laravel).

🌐 Considerar CORS si la UI se sirve desde otro dominio.

9) Mantenimiento y pruebas
bash
Copiar código
# Limpiar cachés
php artisan optimize:clear

# Logs
tail -f storage/logs/laravel.log

# Seeders para demo
php artisan migrate --seed

# Pruebas manuales
# Con IA ON/OFF en Postman → POST /api/search
10) Checklist de entrega (QA)
 README.md actualizado con uso y endpoints.

 public/demo.html y public/demo.js funcionan.

 .env configurado (DB + RERANK_*) y no versionado.

 GET /api/ia-test devuelve 200.

 POST /api/search devuelve 200 con y sin IA.

 Desempeño aceptable con k ≤ 8 y 6–10 candidatos a IA.

 Semillas cargadas y FULLTEXT creado.

