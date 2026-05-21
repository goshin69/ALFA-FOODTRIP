const BASE_URL = '/ALFA/public/';
let currentOffset = 3;
let isLoading = false;
let hasMore = true;
let container = document.getElementById('recetas-container');
let spinner = document.getElementById('loading-spinner');
let noMoreDiv = document.getElementById('no-more');

function initCarousels(parentElement) {
    parentElement.querySelectorAll('.seccion-carrusel').forEach(section => {
        let track = section.querySelector('.carrusel-track');
        let prevBtn = section.querySelector('.carrusel-prev');
        let nextBtn = section.querySelector('.carrusel-next');
        if (!track || !prevBtn || !nextBtn) return;

        let cardWidth = 0;
        let currentIndex = 0;
        let visibleCards = 0;

        function updateButtons() {
            let maxIndex = Math.max(0, track.children.length - visibleCards);
            prevBtn.disabled = currentIndex === 0;
            nextBtn.disabled = currentIndex >= maxIndex;
            prevBtn.style.opacity = prevBtn.disabled ? '0.3' : '0.7';
            nextBtn.style.opacity = nextBtn.disabled ? '0.3' : '0.7';
        }

        function moveCarousel() {
            let translateX = -currentIndex * cardWidth;
            track.style.transform = `translateX(${translateX}px)`;
            updateButtons();
        }

        function recalcWidth() {
            if (window.innerWidth <= 768) {
                track.style.transform = 'none';
                return;
            }
            let firstCard = track.children[0];
            if (!firstCard) return;
            cardWidth = firstCard.offsetWidth + 24;
            let containerWidth = section.querySelector('.carrusel-contenedor').offsetWidth;
            visibleCards = Math.floor(containerWidth / cardWidth);
            if (currentIndex > track.children.length - visibleCards) {
                currentIndex = Math.max(0, track.children.length - visibleCards);
            }
            moveCarousel();
        }

        prevBtn.addEventListener('click', () => {
            if (currentIndex > 0 && window.innerWidth > 768) {
                currentIndex--;
                moveCarousel();
            }
        });
        nextBtn.addEventListener('click', () => {
            let maxIndex = Math.max(0, track.children.length - visibleCards);
            if (currentIndex < maxIndex && window.innerWidth > 768) {
                currentIndex++;
                moveCarousel();
            }
        });
        window.addEventListener('resize', recalcWidth);
        recalcWidth();
    });
}

function attachAdminEvents(sectionElement) {
    sectionElement.querySelectorAll('.receta-card').forEach(card => {
        let btn = card.querySelector('.options-btn');
        let menu = card.querySelector('.options-menu');
        if (btn && menu) {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                document.querySelectorAll('.options-menu').forEach(m => m.classList.add('hidden'));
                menu.classList.toggle('hidden');
            });
            let deleteBtn = menu.querySelector('.option-delete');
            let revisionBtn = menu.querySelector('.option-revision');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    let recetaId = card.dataset.id;
                    if (confirm('Eliminar esta receta? No se puede deshacer.')) {
                        moderarReceta(recetaId, 'eliminar', card);
                    }
                });
            }
            if (revisionBtn) {
                revisionBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    let recetaId = card.dataset.id;
                    if (confirm('Marcar esta receta para revision. Continuar?')) {
                        moderarReceta(recetaId, 'revision', card);
                    }
                });
            }
        }
    });
}

function moderarReceta(recetaId, accion, cardElement) {
    fetch(BASE_URL + 'api/moderar_receta.php', {
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
                let btn = cardElement.querySelector('.options-btn');
                if (btn) btn.disabled = true;
                alert('Receta marcada para revision');
            }
        } else {
            alert('Error: ' + (data.error || 'No se pudo completar la accion'));
        }
    })
    .catch(err => console.error(err));
}

function cargarMasSecciones() {
    if (!hasMore || isLoading) return;
    isLoading = true;
    if (spinner) spinner.style.display = 'block';
    fetch(`${BASE_URL}api/personal_mas.php?offset=${currentOffset}&limit=3`)
        .then(res => res.json())
        .then(data => {
            if (data.ok && data.sections.length) {
                data.sections.forEach(seccion => {
                    let html = generarHTMLSeccion(seccion);
                    container.insertAdjacentHTML('beforeend', html);
                });
                currentOffset = data.next_offset;
                hasMore = data.has_more;
                initCarousels(container);
                if (window.usuarioRol === 'admin') attachAdminEvents(container);
            } else {
                hasMore = false;
            }
            if (!hasMore && noMoreDiv) noMoreDiv.style.display = 'block';
        })
        .catch(err => console.error(err))
        .finally(() => {
            isLoading = false;
            if (spinner) spinner.style.display = 'none';
        });
}

function generarHTMLSeccion(seccion) {
    let cardsHtml = '';
    seccion.recetas.forEach(receta => {
        let imagenSrc = receta.imagen;
        if (imagenSrc) {
            if (imagenSrc.startsWith('/')) imagenSrc = BASE_URL + imagenSrc.substring(1);
            else if (!imagenSrc.startsWith('http')) imagenSrc = BASE_URL + imagenSrc;
        } else {
            imagenSrc = BASE_URL + 'imageness/default_receta.jpg';
        }
        let avatarAutor = BASE_URL + 'assets/img/koali.ico';
        let opcionesHtml = '';
        if (window.usuarioRol === 'admin') {
            opcionesHtml = `
                <div class="card-options">
                    <button class="options-btn"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                    <div class="options-menu hidden">
                        <button class="option-revision">Marcar para revision</button>
                        <button class="option-delete">Eliminar</button>
                    </div>
                </div>
            `;
        }
        cardsHtml += `
            <article class="receta-card" data-id="${receta.id}">
                <div class="card-imagen">
                    ${opcionesHtml}
                    <img src="${escapeHtml(imagenSrc)}" alt="${escapeHtml(receta.titulo)}">
                </div>
                <div class="card-info">
                    <h3>${escapeHtml(receta.titulo)}</h3>
                    <div class="card-footer">
                        <div class="autor">
                            <img src="${avatarAutor}" class="avatar-mini" alt="avatar">
                            <span>${escapeHtml(receta.autor_nombre)}</span>
                        </div>
                        <div class="stats">
                            <span><i class="fa-regular fa-eye"></i> ${receta.total_vistas}</span>
                        </div>
                    </div>
                    <a href="receta.php?id=${receta.id}" class="ver-receta">Ver receta</a>
                </div>
            </article>
        `;
    });
    return `
        <section class="seccion-carrusel">
            <div class="carrusel-header">
                <h2 class="seccion-titulo">${escapeHtml(seccion.nombre)}</h2>
                <div class="carrusel-controls">
                    <button class="carrusel-btn carrusel-prev"><i class="fa-solid fa-chevron-left"></i></button>
                    <button class="carrusel-btn carrusel-next"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
            </div>
            <div class="carrusel-contenedor">
                <div class="carrusel-track">
                    ${cardsHtml}
                </div>
            </div>
        </section>
    `;
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

document.addEventListener('DOMContentLoaded', function() {
    initCarousels(document);
    if (window.usuarioRol === 'admin') attachAdminEvents(document);
    window.addEventListener('scroll', () => {
        if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 800) {
            cargarMasSecciones();
        }
    });
});