document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('receta-form');
  const msg = document.getElementById('mensaje');
  const err = document.getElementById('error');
  if (!form) return;
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(form);
    fetch('api/crear_receta.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(j => {
        if (j.ok) { msg.textContent = 'Receta publicada'; setTimeout(()=>location.href = 'receta.html?id='+encodeURIComponent(j.receta_id),1000); }
        else err.textContent = j.error || 'Error';
      })
      .catch(() => err.textContent = 'Error de conexión');
  });
});
