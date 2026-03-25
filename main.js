document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('recetas-container');
    if (!container) return;

   fetch('/ALFA/public/api/recetas.php')
        .then(resp => {
            if (!resp.ok) throw new Error('Error en la respuesta de la API');
            return resp.json();
        })
        .then(data => {
            if (!data.ok) throw new Error(data.error || 'API returned error');
            renderRecetas(data.recetas, container);
        })
        .catch(err => {
            console.error(err);
            container.innerHTML = '<div class="error-message">No se pudieron cargar las recetas. Intenta más tarde.</div>';
        });
});

function renderRecetas(recetas, container) {
    container.innerHTML = '';
    if (!recetas || recetas.length === 0) {
        container.innerHTML = '<div class="empty-message">Aún no hay recetas. ¡Sé el primero en publicar!</div>';
        return;
    }

    recetas.forEach(r => {
        const card = document.createElement('div');
        card.className = 'receta';
        if (r.autor_rol === 'restaurante') card.classList.add('restaurante');

        const titulo = document.createElement('h3');
        titulo.textContent = r.titulo || 'Sin título';
        card.appendChild(titulo);

        const autor = document.createElement('div');
        autor.className = 'autor';
        autor.textContent = `Publicado por: ${r.autor_nombre || 'Anónimo'}`;
        if (r.autor_rol) autor.innerHTML += ` <span class="rol">(${r.autor_rol})</span>`;
        card.appendChild(autor);

        if (r.descripcion) {
            const desc = document.createElement('p');
            desc.innerHTML = nl2br(escapeHtml(r.descripcion));
            card.appendChild(desc);
        }

        if (r.imagenes && r.imagenes.length > 0) {
            const galeria = document.createElement('div');
            galeria.className = 'imagenes';
            r.imagenes.forEach(src => {
                const lower = String(src).toLowerCase();
                if (lower.match(/\.(mp4|webm|ogg)$/)) {
                    const video = document.createElement('video');
                    video.controls = true;
                    video.src = src;
                    video.style.maxWidth = '100%';
                    galeria.appendChild(video);
                } else {
                    const img = document.createElement('img');
                    img.src = src;
                    img.alt = 'Imagen de receta';
                    img.loading = 'lazy';
                    galeria.appendChild(img);
                }
            });
            card.appendChild(galeria);
        }

        const link = document.createElement('a');
        link.href = `receta.php?id=${encodeURIComponent(r.id)}`;
        link.textContent = 'Ver detalles';
        card.appendChild(link);

        container.appendChild(card);
    });
}

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