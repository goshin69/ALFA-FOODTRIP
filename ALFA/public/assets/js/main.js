const BASE_URL = '/ALFA/';

document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('recetas-container');
    if (!container) return;

    fetch('api/recetas.php')
        .then(resp => resp.json())
        .then(data => {
            if (!data.ok) throw new Error(data.error || 'Error al cargar recetas');
            renderRecetas(data.recetas, container);
        })
        .catch(err => {
            console.error(err);
            container.innerHTML = '<p class="error">No se pudieron cargar las recetas.</p>';
        });
});

function renderRecetas(recetas, container) {
    container.innerHTML = '';
    if (!recetas || recetas.length === 0) {
        container.innerHTML = '<p class="sin-recetas">Aún no hay recetas publicadas. ¡Sé el primero!</p>';
        return;
    }

    const grid = document.createElement('div');
    grid.className = 'recetas-grid';

    recetas.forEach(r => {
        const recetaDiv = document.createElement('article');
        recetaDiv.className = 'receta';

        let imagenSrc = r.imagen;
        if (imagenSrc && !imagenSrc.startsWith('http') && !imagenSrc.startsWith('/')) {
            imagenSrc = BASE_URL + imagenSrc;
        } else if (imagenSrc && imagenSrc.startsWith('/')) {
            imagenSrc = BASE_URL + imagenSrc.substring(1);
        }

        const dificultadIcon = r.dificultad === 'facil' ? '😊' : (r.dificultad === 'media' ? '👍' : '🔥');
        
        recetaDiv.innerHTML = `
            <div class="imagenes"><img src="${imagenSrc}" alt="${escapeHTML(r.titulo)}"></div>
            <h3>${escapeHTML(r.titulo)}</h3>
            <div class="autor">${escapeHTML(r.autor_nombre)} · ${new Date(r.fecha_publicacion).toLocaleDateString()}</div>
            <div class="meta">
                <span><i class="fa-regular fa-clock"></i> ${r.tiempo_preparacion || '?'} min</span>
                <span><i class="fa-regular fa-chart-line"></i> ${dificultadIcon} ${r.dificultad}</span>
            </div>
            <p>${r.descripcion.substring(0, 120)}${r.descripcion.length > 120 ? '...' : ''}</p>
            <a href="receta.php?id=${r.id}">Ver receta</a>
        `;
        grid.appendChild(recetaDiv);
    });

    container.appendChild(grid);
}

function escapeHTML(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}