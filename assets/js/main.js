const BASE_URL = '/ALFA/public/';
let loading = false;
let hasMore = true;
let currentOffset = 0;
let excludeIds = [];
let followingIncluded = false;

document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('recetas-container');
    if (!container) return;
    cargarSeccionesIniciales(container);
    window.addEventListener('scroll', () => {
        if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 500) {
            cargarMasSecciones(container);
        }
    });
});

function cargarSeccionesIniciales(container) {
    if (loading) return;
    loading = true;
    const params = new URLSearchParams({
        offset: 0,
        limit: 3,
        include_following: followingIncluded ? 0 : 1,
        exclude_ids: excludeIds.join(',')
    });
    fetch(`api/feed.php?${params.toString()}`)
        .then(resp => resp.json())
        .then(data => {
            if (!data.ok) throw new Error('Error al cargar feed');
            renderSecciones(data.secciones, container);
            if (data.secciones.length) {
                const firstSection = data.secciones[0];
                if (firstSection && firstSection.nombre === 'Siguiendo') {
                    followingIncluded = true;
                }
                data.secciones.forEach(sec => {
                    if (sec.etiqueta_id && sec.etiqueta_id !== 0) {
                        excludeIds.push(sec.etiqueta_id);
                    }
                });
            }
            currentOffset = 3;
            hasMore = data.hasMore;
            if (!hasMore) {
                const spinner = document.getElementById('loading-spinner');
                if (spinner) spinner.style.display = 'none';
            }
        })
        .catch(err => {
            console.error(err);
            container.innerHTML = '<p class="error">No se pudo cargar el feed.</p>';
        })
        .finally(() => { loading = false; });
}

function cargarMasSecciones(container) {
    if (!hasMore || loading) return;
    loading = true;
    const spinner = document.getElementById('loading-spinner');
    if (spinner) spinner.style.display = 'block';
    const params = new URLSearchParams({
        offset: currentOffset,
        limit: 3,
        include_following: 0,
        exclude_ids: excludeIds.join(',')
    });
    fetch(`api/feed.php?${params.toString()}`)
        .then(resp => resp.json())
        .then(data => {
            if (!data.ok) throw new Error('Error cargando más secciones');
            if (data.secciones.length) {
                renderSecciones(data.secciones, container);
                data.secciones.forEach(sec => {
                    if (sec.etiqueta_id && sec.etiqueta_id !== 0) {
                        excludeIds.push(sec.etiqueta_id);
                    }
                });
                currentOffset += data.secciones.length;
                hasMore = data.hasMore;
            } else {
                hasMore = false;
            }
        })
        .catch(err => console.error(err))
        .finally(() => {
            loading = false;
            if (spinner) spinner.style.display = 'none';
        });
}

function renderSecciones(secciones, container) {
    secciones.forEach(seccion => {
        const sectionDiv = document.createElement('section');
        sectionDiv.className = 'seccion-carrusel';

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

        const carruselContainer = document.createElement('div');
        carruselContainer.className = 'carrusel-contenedor';
        const track = document.createElement('div');
        track.className = 'carrusel-track';

        seccion.recetas.forEach(receta => {
            const card = document.createElement('article');
            card.className = 'receta-card';
            card.dataset.id = receta.id;

            let imagenSrc = receta.imagen;
            if (imagenSrc) {
                if (imagenSrc.startsWith('/')) {
                    imagenSrc = BASE_URL + imagenSrc.substring(1);
                } else if (!imagenSrc.startsWith('http')) {
                    imagenSrc = BASE_URL + imagenSrc;
                }
            } else {
                imagenSrc = BASE_URL + 'imageness/default_receta.jpg';
            }

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

            const avatarAutor = receta.autor_id ? `${BASE_URL}uploads/perfiles/perfil_${receta.autor_id}.webp` : `${BASE_URL}assets/img/koali.ico`;

            card.innerHTML = `
                <div class="card-imagen">
                    ${opcionesHtml}
                    <img src="${imagenSrc}" alt="${escapeHTML(receta.titulo)}">
                </div>
                <div class="card-info">
                    <h3>${escapeHTML(receta.titulo)}</h3>
                    <div class="card-footer">
                        <div class="autor">
                            <img src="${avatarAutor}" class="avatar-mini" alt="avatar">
                            <span>${escapeHTML(receta.autor_nombre)}</span>
                        </div>
                        <div class="stats"><span><i class="fa-regular fa-eye"></i> ${numberFormat(receta.total_vistas)}</span></div>
                    </div>
                    <a href="receta.php?id=${receta.id}" class="ver-receta">Ver receta</a>
                </div>
            `;
            track.appendChild(card);
        });

        carruselContainer.appendChild(track);
        sectionDiv.appendChild(carruselContainer);
        container.appendChild(sectionDiv);

        iniciarCarrusel(sectionDiv);
    });

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

function iniciarCarrusel(section) {
    const track = section.querySelector('.carrusel-track');
    const prevBtn = section.querySelector('.carrusel-prev');
    const nextBtn = section.querySelector('.carrusel-next');
    if (!track || !prevBtn || !nextBtn) return;

    let cardWidth = 0;
    let currentIndex = 0;
    let visibleCards = 0;

    function updateButtons() {
        if (window.innerWidth <= 768) {
            prevBtn.style.display = 'none';
            nextBtn.style.display = 'none';
            return;
        }
        prevBtn.style.display = '';
        nextBtn.style.display = '';
        const maxIndex = Math.max(0, track.children.length - visibleCards);
        prevBtn.disabled = currentIndex === 0;
        nextBtn.disabled = currentIndex >= maxIndex;
        prevBtn.style.opacity = prevBtn.disabled ? '0.3' : '0.7';
        nextBtn.style.opacity = nextBtn.disabled ? '0.3' : '0.7';
    }

    function moveCarousel() {
        if (window.innerWidth <= 768) return;
        const translateX = -currentIndex * cardWidth;
        track.style.transform = `translateX(${translateX}px)`;
        updateButtons();
    }

    function recalcWidth() {
        if (window.innerWidth <= 768) {
            track.style.transform = 'none';
            updateButtons();
            return;
        }
        const firstCard = track.children[0];
        if (!firstCard) return;
        cardWidth = firstCard.offsetWidth + 24;
        const containerWidth = section.querySelector('.carrusel-contenedor').offsetWidth;
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
        const maxIndex = Math.max(0, track.children.length - visibleCards);
        if (currentIndex < maxIndex && window.innerWidth > 768) {
            currentIndex++;
            moveCarousel();
        }
    });
    window.addEventListener('resize', recalcWidth);
    recalcWidth();
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

function numberFormat(num) {
    if (!num) return '0';
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}