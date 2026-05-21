document.addEventListener('DOMContentLoaded', function() {
    const loader = document.getElementById('loader-wrapper');
    if (loader) {
        setTimeout(() => {
            loader.style.opacity = '0';
            setTimeout(() => {
                loader.style.visibility = 'hidden';
            }, 800);
        }, 500);
    }

    const urlParams = new URLSearchParams(window.location.search);
    const recetaId = urlParams.get('id');
    if (!recetaId) {
        document.getElementById('receta-detalle').innerHTML = '<p>No se especificó una receta.</p>';
        return;
    }

    document.getElementById('receta-id-input').value = recetaId;

    fetch(`/api/receta.php?id=${recetaId}`)
        .then(response => response.json())
        .then(data => {
            if (data.ok) {
                renderReceta(data.receta);
                renderComentarios(data.comentarios);
            } else {
                document.getElementById('receta-detalle').innerHTML = '<p>Error al cargar la receta.</p>';
            }
        })
        .catch(err => {
            console.error(err);
            document.getElementById('receta-detalle').innerHTML = '<p>Error de conexión.</p>';
        });

    function renderReceta(receta) {
        const div = document.getElementById('receta-detalle');
        div.innerHTML = `
            <h2>${escapeHtml(receta.titulo)}</h2>
            <p class="autor">Publicado por: ${escapeHtml(receta.autor_nombre)} (${receta.autor_rol})</p>
            <p>${nl2br(escapeHtml(receta.descripcion))}</p>
            ${receta.imagenes && receta.imagenes.length > 0 ? renderImagenes(receta.imagenes) : ''}
        `;
    }

    function renderImagenes(imagenes) {
        let html = '<div class="imagenes">';
        imagenes.forEach(src => {
            const lower = src.toLowerCase();
            if (lower.match(/\.(mp4|webm|ogg)$/)) {
                html += `<video controls src="${src}" style="max-width:100%;"></video>`;
            } else {
                html += `<img src="${src}" alt="Imagen de receta" style="max-width:200px;">`;
            }
        });
        html += '</div>';
        return html;
    }

    function renderComentarios(comentarios) {
        const container = document.getElementById('comentarios-lista');
        if (!comentarios || comentarios.length === 0) {
            container.innerHTML = '<p>No hay comentarios aún. Sé el primero en comentar.</p>';
            return;
        }
        let html = '';
        comentarios.forEach(c => {
            html += `
                <div class="comentario">
                    <p><strong>${escapeHtml(c.usuario_nombre)}</strong> - Puntuación: ${c.puntuacion}/5</p>
                    <p>${nl2br(escapeHtml(c.comentario))}</p>
                    <small>${new Date(c.fecha).toLocaleString()}</small>
                </div>
                <hr>
            `;
        });
        container.innerHTML = html;
    }

    const form = document.getElementById('comentario-form');
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        try {
            const response = await fetch('/api/comentarios.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (data.ok) {
                alert('Comentario publicado');
                location.reload();
            } else {
                document.getElementById('error').textContent = data.error || 'Error al publicar';
            }
        } catch (err) {
            document.getElementById('error').textContent = 'Error de conexión';
        }
    });

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function nl2br(str) {
        return str.replace(/\r?\n/g, '<br>');
    }
});