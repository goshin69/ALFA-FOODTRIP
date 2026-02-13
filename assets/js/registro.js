document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('registro-form');
  const err = document.getElementById('error');
  if (!form) return;
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(form);
    fetch('api/registro.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(j => {
        if (j.ok) location.href = 'login.html'; else err.textContent = j.error || 'Error';
      })
      .catch(() => err.textContent = 'Error de conexión');
  });
});
