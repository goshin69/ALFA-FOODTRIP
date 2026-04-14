document.addEventListener('DOMContentLoaded', function() {
    const recetaId = new URLSearchParams(window.location.search).get('id');
    if (!recetaId) return;

    if (window.usuarioRol === 'admin') {
        const btn = document.getElementById('btn-moderar');
        const menu = document.getElementById('moderar-menu');
        if (btn && menu) {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                menu.classList.toggle('hidden');
            });
            document.addEventListener('click', () => menu.classList.add('hidden'));

            document.getElementById('revision-btn')?.addEventListener('click', () => {
                if (confirm('Marcar esta receta para revisión?')) {
                    moderar(recetaId, 'revision');
                }
            });
            document.getElementById('eliminar-btn')?.addEventListener('click', () => {
                if (confirm('¿Eliminar esta receta permanentemente?')) {
                    moderar(recetaId, 'eliminar');
                }
            });
        }
    }
});

function moderar(recetaId, accion) {
    fetch('api/moderar_receta.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `receta_id=${recetaId}&accion=${accion}`
    })
    .then(resp => resp.json())
    .then(data => {
        if (data.ok) {
            alert('Acción completada');
            if (accion === 'eliminar') {
                window.location.href = 'index.php';
            } else {
                location.reload();
            }
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error de conexión');
    });
}
