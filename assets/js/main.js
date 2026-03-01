document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('recetas-container');
    if (!container) return;

    fetch('api/recetas.php')
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
            container.textContent = 'No se pudieron cargar las recetas.';
        });
});

function renderRecetas(recetas, container) {
    container.innerHTML = '';
    if (!recetas || recetas.length === 0) {
        container.textContent = 'Aún no hay recetas. ¡Sé el primero en publicar!';
        return;
    }

    recetas.forEach(r => {
        const div = document.createElement('div');
        div.className = 'receta ' + (r.autor_rol === 'restaurante' ? 'restaurante' : '');

        const h3 = document.createElement('h3');
        h3.textContent = r.titulo || 'Sin título';
        div.appendChild(h3);

        const pAutor = document.createElement('p');
        pAutor.className = 'autor';
        pAutor.textContent = `Publicado por: ${r.autor_nombre || 'Anónimo'} (${r.autor_rol || ''})`;
        div.appendChild(pAutor);

        const pDesc = document.createElement('p');
        pDesc.innerHTML = (r.descripcion) ? nl2br(escapeHtml(r.descripcion)) : '';
        div.appendChild(pDesc);

        if (r.imagenes && r.imagenes.length > 0) {
            const g = document.createElement('div');
            g.className = 'imagenes';
            r.imagenes.forEach(src => {
                const lower = String(src).toLowerCase();
                if (lower.match(/\.(mp4|webm|ogg)$/)) {
                    const v = document.createElement('video');
                    v.controls = true;
                    v.src = src;
                    v.style.maxWidth = '100%';
                    g.appendChild(v);
                } else {
                    const img = document.createElement('img');
                    img.src = src;
                    img.alt = 'Imagen de receta';
                    g.appendChild(img);
                }
            });
            div.appendChild(g);
        }

        const pLink = document.createElement('p');
        const a = document.createElement('a');
        a.href = `receta.html?id=${encodeURIComponent(r.id)}`;
        a.textContent = 'Ver detalles y comentar';
        pLink.appendChild(a);
        div.appendChild(pLink);

        container.appendChild(div);
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

