document.addEventListener('DOMContentLoaded', function() {
  const params = new URLSearchParams(window.location.search);
  const id = params.get('id');
  const recCont = document.getElementById('receta');
  const comCont = document.getElementById('comentarios');
  const form = document.getElementById('comentario-form');
  const err = document.getElementById('error');
  if (!id) { recCont.textContent = 'ID inválido'; return; }
  fetch('api/receta.php?id='+encodeURIComponent(id))
    .then(r => r.json())
    .then(j => {
      if (!j.ok) { recCont.textContent = j.error || 'Error'; return; }
      const rdata = j.receta;
      let html = '<h1>'+escapeHtml(rdata.titulo)+'</h1>';
      html += '<p><strong>Publicado por:</strong> '+escapeHtml(rdata.autor_nombre)+' ('+escapeHtml(rdata.autor_rol)+')</p>';
      html += '<p>'+nl2br(escapeHtml(rdata.descripcion))+'</p>';
      if (j.imagenes && j.imagenes.length) { html += '<div class="imagenes-detalle">'; j.imagenes.forEach(src=>{ const lower = String(src).toLowerCase(); if (lower.match(/\.(mp4|webm|ogg)$/)) { html += '<video controls src="'+escapeHtml(src)+'" style="max-width:100%"></video>'; } else { html += '<img src="'+escapeHtml(src)+'" alt="imagen">'; } }); html += '</div>'; }
      html += '<h2>Puntuación promedio: <span>'+j.promedio+' / 5 ★</span></h2>';
      recCont.innerHTML = html;
      if (j.comentarios && j.comentarios.length) {
        comCont.innerHTML = '';
        j.comentarios.forEach(c=>{
          const d = document.createElement('div'); d.className = 'comentario'; d.innerHTML = '<p><strong>'+escapeHtml(c.usuario_nombre)+'</strong> - <small>'+escapeHtml(c.fecha)+'</small><br><span class="puntuacion-estrellas">'+escapeHtml('★'.repeat(c.puntuacion))+'</span></p><p>'+nl2br(escapeHtml(c.comentario))+'</p>';
          comCont.appendChild(d);
        });
      } else { comCont.textContent = 'Aún no hay comentarios.'; }
    })
    .catch(()=> recCont.textContent = 'Error al cargar');
  if (form) {
    form.addEventListener('submit', function(e){
      e.preventDefault();
      const fd = new FormData(form); fd.append('receta_id', id);
      fetch('api/comentarios.php', { method: 'POST', body: fd })
        .then(r=>r.json())
        .then(j=>{ if (j.ok) location.reload(); else err.textContent = j.error || 'Error'; })
        .catch(()=> err.textContent = 'Error de conexión');
    });
  }
});
function escapeHtml(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }
function nl2br(s){ return String(s).replace(/\r?\n/g,'<br>'); }
