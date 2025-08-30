<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>{{ config('app.name') }} — Buscador IA</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Tailwind vía CDN (rápido para demo) -->
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-900">
  <header class="bg-white border-b">
    <div class="max-w-5xl mx-auto px-4 py-5 flex items-center justify-between">
      <h1 class="text-xl font-semibold">Buscador de Servicios (IA)</h1>
      <span class="text-sm text-slate-500">Backend: /api/search</span>
    </div>
  </header>

  <main class="max-w-5xl mx-auto px-4 py-6">
    <!-- FORM -->
    <div class="bg-white shadow-sm rounded-2xl p-5 border">
      <form id="searchForm" class="grid gap-4 md:grid-cols-12 items-end">
        <div class="md:col-span-6">
          <label class="block text-sm font-medium mb-1">¿Qué necesitas?</label>
          <input id="q" type="text" placeholder="Ej: instalar cámaras, arreglar calefón, mejorar wifi…"
                 class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2" required>
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm font-medium mb-1">Categoría (opcional)</label>
          <select id="categoria" class="w-full rounded-xl border-slate-300 px-3 py-2">
            <option value="">(cualquiera)</option>
            <option>Seguridad</option>
            <option>Plomería</option>
            <option>Electricidad</option>
            <option>Cerrajería</option>
            <option>Redes</option>
            <option>Pintura</option>
          </select>
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm font-medium mb-1">Barrio (opcional)</label>
          <select id="barrio" class="w-full rounded-xl border-slate-300 px-3 py-2">
            <option value="">(cualquiera)</option>
            <option>Amaguaña</option>
            <option>Conocoto</option>
            <option>Sangolquí</option>
            <option>Alangasí</option>
            <option>San Rafael</option>
            <option>La Merced</option>
          </select>
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm font-medium mb-1">Top K</label>
          <input id="k" type="number" min="1" max="20" value="8"
                 class="w-full rounded-xl border-slate-300 px-3 py-2">
        </div>

        <div class="md:col-span-12 flex flex-wrap items-center gap-4">
          <label class="inline-flex items-center gap-2">
            <input id="useIA" type="checkbox" class="size-4" checked>
            <span class="text-sm">Usar IA de re-ranking</span>
          </label>

          <button id="btnUbicacion" type="button"
                  class="rounded-xl border px-3 py-1.5 text-sm bg-slate-100 hover:bg-slate-200">
            Usar mi ubicación
          </button>
          <span id="coord" class="text-xs text-slate-500"></span>

          <button type="submit"
                  class="rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 ml-auto">
            Buscar
          </button>
        </div>
      </form>
    </div>

    <!-- ESTADO -->
    <div id="status" class="mt-4 text-sm text-slate-600"></div>

    <!-- RESULTADOS -->
    <div id="results" class="mt-6 grid gap-4"></div>
  </main>

  <footer class="py-10 text-center text-xs text-slate-500">
    {{ config('app.name') }} — Demo “IA como servicio” (re-ranking + señales)
  </footer>

<script>
const $ = (sel) => document.querySelector(sel);

let userLat = null, userLon = null;

$('#btnUbicacion').addEventListener('click', () => {
  if (!navigator.geolocation) {
    $('#coord').textContent = 'Tu navegador no soporta geolocalización.';
    return;
  }
  $('#coord').textContent = 'Obteniendo ubicación…';
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      userLat = +pos.coords.latitude.toFixed(6);
      userLon = +pos.coords.longitude.toFixed(6);
      $('#coord').textContent = `Lat ${userLat}, Lon ${userLon}`;
    },
    (err) => {
      $('#coord').textContent = 'No se pudo obtener la ubicación.';
      console.warn(err);
    },
    { enableHighAccuracy: true, timeout: 8000 }
  );
});

const apiBase = location.origin; // mismo host/puerto

$('#searchForm').addEventListener('submit', async (e) => {
  e.preventDefault();

  const payload = {
    query: $('#q').value.trim(),
    categoria: $('#categoria').value || null,
    barrio: $('#barrio').value || null,
    k: parseInt($('#k').value || '8', 10),
    use_ia: $('#useIA').checked,
  };
  if (userLat != null && userLon != null) {
    payload.user_lat = userLat;
    payload.user_lon = userLon;
  }

  $('#status').innerHTML = 'Buscando…';
  $('#results').innerHTML = '';

  try {
    const res = await fetch(`${apiBase}/api/search`, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(payload)
    });
    const data = await res.json();

    const meta = data.meta || {};
    const ia = meta.ia || {};
    $('#status').innerHTML = `
      <div class="bg-white border rounded-xl p-3">
        <div><b>Consulta:</b> ${meta.query ?? ''}</div>
        <div class="text-xs text-slate-500">
          Estrategia: ${meta.estrategia ?? '-'}
          | IA: <b>${ia.ok ? 'ON' : 'OFF'}</b> (${ia.proveedor ?? '-'})
          | enviados a IA: ${ia.candidatos_enviados_a_ia ?? 0}
        </div>
      </div>
    `;

    if (!data.items || data.items.length === 0) {
      $('#results').innerHTML = `<div class="text-slate-500">Sin resultados. Prueba otras palabras o quita filtros.</div>`;
      return;
    }

    const frag = document.createDocumentFragment();
    data.items.forEach(item => {
      const card = document.createElement('div');
      card.className = 'bg-white border rounded-2xl p-4 shadow-sm';

      const badges = (item.badges||[]).map(b => 
        `<span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-1 rounded">${b}</span>`).join(' ');

      card.innerHTML = `
        <div class="flex items-start justify-between gap-3">
          <div>
            <div class="text-sm text-slate-500">${item.categoria} · ${item.barrio}</div>
            <div class="text-lg font-semibold">${item.titulo}</div>
            <div class="text-slate-600 mt-1">${item.descripcion}</div>
            <div class="mt-2 flex items-center gap-2 text-sm">
              <span class="px-2 py-0.5 rounded bg-slate-100">Proveedor: ${item.proveedor}</span>
              <span class="px-2 py-0.5 rounded bg-slate-100">Rating: ${item.rating.toFixed(1)}</span>
              <span class="px-2 py-0.5 rounded bg-slate-100">IA: ${((item.ia_score ?? 0)*100).toFixed(0)}%</span>
              <span class="px-2 py-0.5 rounded bg-indigo-100 text-indigo-700">Score: ${item.final_score.toFixed(3)}</span>
              ${badges}
            </div>
          </div>
        </div>
      `;
      frag.appendChild(card);
    });
    $('#results').appendChild(frag);

  } catch (err) {
    console.error(err);
    $('#status').innerHTML = `<div class="text-red-600">Error al buscar. Revisa la consola o el log.</div>`;
  }
});

// Sugerencia: precargar un ejemplo para la demo
document.addEventListener('DOMContentLoaded', () => {
  $('#q').value = 'instalar cámaras';
});
</script>

</body>
</html>
