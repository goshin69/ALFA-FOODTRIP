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
        div.className = 'receta';

        const h3 = document.createElement('h3');
        h3.textContent = r.titulo || 'Sin título';
        div.appendChild(h3);

        const pAutor = document.createElement('p');
        pAutor.className = 'autor';
        pAutor.textContent = `Publicado por: ${r.autor_nombre || 'Anónimo'}`;
        div.appendChild(pAutor);

        if (r.imagen) {
            const imgContainer = document.createElement('div');
            imgContainer.className = 'imagenes';
            const img = document.createElement('img');
            img.src = r.imagen;
            img.alt = 'Imagen de receta';
            imgContainer.appendChild(img);
            div.appendChild(imgContainer);
        }

        const pDesc = document.createElement('p');
        pDesc.innerHTML = r.descripcion ? r.descripcion.substring(0, 200) + (r.descripcion.length > 200 ? '...' : '') : '';
        div.appendChild(pDesc);

        const pLink = document.createElement('p');
        const a = document.createElement('a');
        a.href = `receta.php?id=${encodeURIComponent(r.id)}`;
        a.textContent = 'Ver detalles y comentar';
        pLink.appendChild(a);
        div.appendChild(pLink);

        container.appendChild(div);
    });
}