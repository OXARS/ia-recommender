```md
# Informe — Proyecto Integrador (IA como servicio)

## Objetivo
Construir una plataforma web que use una API de IA para mejorar la búsqueda de servicios locales.

## Arquitectura
- **Backend**: Laravel (PHP 8.2), MySQL.
- **IA**: Cohere Rerank (modelo multilingual).
- **Flujo**:
  1) Recuperar candidatos con FULLTEXT/LIKE.  
  2) Enviar a IA Rerank (top_n = k).  
  3) Mezclar IA + proximidad + rating.  
  4) Mostrar en UI (Blade + Tailwind).

## Cumplimiento de requisitos
- Frontend web accesible: **Sí** (`/`)
- Integración IA desde backend: **Sí** (Cohere/Jina).
- Lógica explicada: **Sí** (flujo y fórmula).
- Funcionalidades mínimas: inputs, respuesta, validaciones y manejo de errores: **Sí**.

## API de ejemplo
`POST /api/search`
```json
{ "query": "instalar cámaras", "k": 8, "use_ia": true }
