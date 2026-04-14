const BASE_URL = '/ALFA/';

document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('recetas-container');
    if (!container) return;

    fetch('api/recetas.php')
        .then(resp => resp.json())
        .then(data => {
            if (!data.ok) throw new Error('Error al cargar recetas');
            if (!data.secciones || data.secciones.length === 0) {
                container.innerHTML = '<p class="sin-recetas">Aún no hay recetas publicadas.</p>';
                return;
            }
            renderSecciones(data.secciones, container);
        })
        .catch(err => {
            console.error(err);
            container.innerHTML = '<p class="error">No se pudieron cargar las recetas.</p>';
        });
});

function renderSecciones(secciones, container) {
    container.innerHTML = '';

    secciones.forEach(seccion => {
        const sectionDiv = document.createElement('section');
        sectionDiv.className = 'seccion-carrusel';

        // Header con título y botones
        const header = document.createElement('div');
        header.className = 'carrusel-header';

        const title = document.createElement('h2');
        title.className = 'seccion-titulo';
        title.textContent = seccion.nombre;
        header.appendChild(title);

        const controls = document.createElement('div');
        controls.className = 'carrusel-controls';

        const btnPrev = document.createElement('button');
        btnPrev.className = 'carrusel-btn carrusel-prev';
        btnPrev.innerHTML = '<i class="fa-solid fa-chevron-left"></i>';
        const btnNext = document.createElement('button');
        btnNext.className = 'carrusel-btn carrusel-next';
        btnNext.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
        controls.appendChild(btnPrev);
        controls.appendChild(btnNext);
        header.appendChild(controls);
        sectionDiv.appendChild(header);

        // Contenedor del carrusel
        const carruselContainer = document.createElement('div');
        carruselContainer.className = 'carrusel-contenedor';

        const track = document.createElement('div');
        track.className = 'carrusel-track';

        seccion.recetas.forEach(receta => {
            const card = document.createElement('article');
            card.className = 'receta-card';
            card.dataset.id = receta.id;

            let imagenSrc = receta.imagen;
            if (imagenSrc && !imagenSrc.startsWith('http') && !imagenSrc.startsWith('/')) {
                imagenSrc = BASE_URL + imagenSrc;
            } else if (imagenSrc && imagenSrc.startsWith('/')) {
                imagenSrc = BASE_URL + imagenSrc.substring(1);
            }

            const dificultadIcon = receta.dificultad === 'facil' ? ' ' : (receta.dificultad === 'media' ? ' ' : ' ');

            let opcionesHtml = '';
            if (window.usuarioRol === 'admin') {
                opcionesHtml = `
                    <div class="card-options">
                        <button class="options-btn"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                        <div class="options-menu hidden">
                            <button class="option-revision">Marcar para revisión</button>
                            <button class="option-delete">Eliminar</button>
                        </div>
                    </div>
                `;
            }

            card.innerHTML = `
                <div class="card-imagen">
                    ${opcionesHtml}
                    <img src="${imagenSrc}" alt="${escapeHTML(receta.titulo)}">
                </div>
                <div class="card-info">
                    <h3>${escapeHTML(receta.titulo)}</h3>
                    <div class="autor">${escapeHTML(receta.autor_nombre)}</div>
                    <div class="meta">
                        <span><i class="fa-regular fa-clock"></i> ${receta.tiempo_preparacion || '?'} min</span>
                        <span>${dificultadIcon}</span>
                    </div>
                    <a href="receta.php?id=${receta.id}" class="ver-receta">Ver receta</a>
                </div>
            `;
            track.appendChild(card);
        });

        carruselContainer.appendChild(track);
        sectionDiv.appendChild(carruselContainer);
        container.appendChild(sectionDiv);

        // Configurar el carrusel con desplazamiento por botones
        const trackElement = track;
        const cards = trackElement.children;
        const cardWidth = cards[0] ? cards[0].offsetWidth + 24 : 0; // 24 es el gap
        let currentIndex = 0;
        const visibleCards = Math.floor(carruselContainer.offsetWidth / (cardWidth));

        function updateButtons() {
            const maxIndex = Math.max(0, cards.length - visibleCards);
            btnPrev.disabled = currentIndex === 0;
            btnNext.disabled = currentIndex >= maxIndex;
            btnPrev.style.opacity = btnPrev.disabled ? '0.3' : '0.7';
            btnNext.style.opacity = btnNext.disabled ? '0.3' : '0.7';
        }

        function moveCarousel() {
            const translateX = -currentIndex * (cardWidth);
            trackElement.style.transform = `translateX(${translateX}px)`;
            updateButtons();
        }

        btnPrev.addEventListener('click', () => {
            if (currentIndex > 0) {
                currentIndex--;
                moveCarousel();
            }
        });

        btnNext.addEventListener('click', () => {
            const maxIndex = Math.max(0, cards.length - visibleCards);
            if (currentIndex < maxIndex) {
                currentIndex++;
                moveCarousel();
            }
        });

        // Recalcular cuando la ventana cambie de tamaño
        window.addEventListener('resize', () => {
            const newCardWidth = cards[0] ? cards[0].offsetWidth + 24 : 0;
            if (newCardWidth !== cardWidth) {
                currentIndex = 0;
                moveCarousel();
            }
        });

        updateButtons();
    });

    // Inicializar menús de administrador si existen
    if (window.usuarioRol === 'admin') {
        document.querySelectorAll('.receta-card').forEach(card => {
            const btn = card.querySelector('.options-btn');
            const menu = card.querySelector('.options-menu');
            if (btn && menu) {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    document.querySelectorAll('.options-menu').forEach(m => m.classList.add('hidden'));
                    menu.classList.toggle('hidden');
                });
                menu.querySelector('.option-delete')?.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const recetaId = card.dataset.id;
                    if (confirm('¿Eliminar esta receta? No se puede deshacer.')) {
                        moderarReceta(recetaId, 'eliminar', card);
                    }
                });
                menu.querySelector('.option-revision')?.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const recetaId = card.dataset.id;
                    if (confirm('Marcar esta receta para revisión. ¿Continuar?')) {
                        moderarReceta(recetaId, 'revision', card);
                    }
                });
            }
        });
        document.addEventListener('click', () => {
            document.querySelectorAll('.options-menu').forEach(m => m.classList.add('hidden'));
        });
    }
}

function moderarReceta(recetaId, accion, cardElement) {
    fetch('api/moderar_receta.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `receta_id=${recetaId}&accion=${accion}`
    })
    .then(resp => resp.json())
    .then(data => {
        if (data.ok) {
            if (accion === 'eliminar') {
                cardElement.remove();
            } else {
                cardElement.style.opacity = '0.5';
                const btn = cardElement.querySelector('.options-btn');
                if (btn) btn.disabled = true;
                alert('Receta marcada para revisión');
            }
        } else {
            alert('Error: ' + (data.error || 'No se pudo completar la acción'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error de conexión');
    });
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