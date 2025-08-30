const $ = s => document.querySelector(s);

$('#btn').onclick = async () => {
  document.querySelector('#meta').textContent = 'Buscando...';
  document.querySelector('#res').innerHTML = '';

  const body = { query: document.querySelector('#q').value.trim(), k: 8, use_ia: document.querySelector('#useIA').checked };

  const r = await fetch('/api/search', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(body)
  });

  const data = await r.json();
  const ia = data.meta?.ia || {};
  document.querySelector('#meta').innerHTML =
    `IA: <b>${ia.ok ? 'ON' : 'OFF'}</b> (${ia.proveedor||'-'}) | candidatos a IA: ${ia.candidatos_enviados_a_ia||0}`;

  (data.items||[]).forEach(it=>{
    const el = document.createElement('div');
    el.className='bg-white border rounded-xl p-4';
    el.innerHTML = `
      <div class="text-sm text-slate-500">${it.categoria} · ${it.barrio}</div>
      <div class="text-lg font-semibold">${it.titulo}</div>
      <div class="text-slate-600">${it.descripcion}</div>
      <div class="mt-1 text-sm">
        Proveedor: ${it.proveedor}
        · Rating: ${it.rating?.toFixed?.(1)}
        · IA: ${((it.ia_score||0)*100).toFixed(0)}%
        · Score: ${it.final_score?.toFixed?.(3)}
      </div>`;
    document.querySelector('#res').appendChild(el);
  });
};
