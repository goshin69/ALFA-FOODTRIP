(function() {
    let offset = parseInt(document.getElementById('secciones-container').dataset.offset || 0);
    let loading = false;
    let hasMore = true;
    const spinner = document.getElementById('loading-spinner');
    const containerSecciones = document.getElementById('secciones-container');

    function initCarousel(container) {
        const track = container.querySelector('.carrusel-track');
        const section = container.closest('.seccion-personalizada');
        const prevBtn = section.querySelector('.carrusel-prev');
        const nextBtn = section.querySelector('.carrusel-next');
        if (!track || !prevBtn || !nextBtn) return;

        let currentIndex = 0;
        let cardWidth = 0;
        let visibleCards = 0;

        function updateButtons() {
            const maxIndex = Math.max(0, track.children.length - visibleCards);
            prevBtn.disabled = currentIndex === 0;
            nextBtn.disabled = currentIndex >= maxIndex;
            prevBtn.style.opacity = prevBtn.disabled ? '0.3' : '0.7';
            nextBtn.style.opacity = nextBtn.disabled ? '0.3' : '0.7';
        }

        function moveCarousel() {
            const translateX = -currentIndex * (cardWidth + 24);
            track.style.transform = `translateX(${translateX}px)`;
            updateButtons();
        }

        function recalcWidth() {
            if (window.innerWidth <= 768) {
                track.style.transform = 'none';
                return;
            }
            const firstCard = track.querySelector('.receta-card');
            if (!firstCard) return;
            cardWidth = firstCard.offsetWidth;
            const containerWidth = container.offsetWidth;
            visibleCards = Math.floor(containerWidth / (cardWidth + 24));
            if (currentIndex > track.children.length - visibleCards) {
                currentIndex = Math.max(0, track.children.length - visibleCards);
            }
            moveCarousel();
        }

        prevBtn.addEventListener('click', () => { if (currentIndex > 0) { currentIndex--; moveCarousel(); } });
        nextBtn.addEventListener('click', () => {
            const maxIndex = Math.max(0, track.children.length - visibleCards);
            if (currentIndex < maxIndex) { currentIndex++; moveCarousel(); }
        });
        window.addEventListener('resize', recalcWidth);
        recalcWidth();
    }

    function initAllCarousels() {
        document.querySelectorAll('.carrusel-contenedor').forEach(initCarousel);
    }

    function cargarMasSecciones() {
        if (!hasMore || loading) return;
        loading = true;
        if (spinner) spinner.style.display = 'block';
        fetch(`api/index.php?offset=${offset}&limit=3`)
            .then(res => res.json())
            .then(data => {
                if (data.ok && data.secciones.length) {
                    data.secciones.forEach(seccion => {
                        const html = generarSeccionHTML(seccion);
                        containerSecciones.insertAdjacentHTML('beforeend', html);
                        const nuevaSeccion = containerSecciones.lastElementChild;
                        const carruselDiv = nuevaSeccion.querySelector('.carrusel-contenedor');
                        if (carruselDiv) initCarousel(carruselDiv);
                    });
                    offset += data.secciones.length;
                    if (data.secciones.length < 3) hasMore = false;
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

    function generarSeccionHTML(seccion) {
        let cardsHtml = '';
        seccion.recetas.forEach(receta => {
            let imagenSrc = receta.imagen;
            if (imagenSrc.startsWith('/')) imagenSrc = '/ALFA/public/' + imagenSrc.substring(1);
            else if (!imagenSrc.startsWith('http')) imagenSrc = '/ALFA/public/' + imagenSrc;
            const avatarUrl = `/ALFA/public/uploads/perfiles/perfil_${receta.autor_id}.webp`;
            cardsHtml += `
                <article class="receta-card">
                    <a href="/ALFA/public/receta.php?id=${receta.id}" class="card-link">
                        <div class="card-image">
                            <img src="${escapeHtml(imagenSrc)}" alt="${escapeHtml(receta.titulo)}">
                            <span class="tiempo-badge"><i class="fa-regular fa-clock"></i> ${receta.tiempo_preparacion} min</span>
                        </div>
                        <div class="card-body">
                            <h3>${escapeHtml(receta.titulo)}</h3>
                            <p class="descripcion">${escapeHtml(receta.descripcion.substring(0,100))}...</p>
                            <div class="card-footer">
                                <div class="autor">
                                    <img src="${escapeHtml(avatarUrl)}" class="avatar-mini" alt="avatar" onerror="this.onerror=null;this.src='/ALFA/public/assets/img/koali.ico'">
                                    <span>${escapeHtml(receta.autor_nombre)}</span>
                                </div>
                                <div class="stats"><span><i class="fa-regular fa-eye"></i> ${receta.total_vistas}</span></div>
                            </div>
                        </div>
                    </a>
                </article>`;
        });
        return `
            <section class="bloque seccion-personalizada">
                <div class="seccion-header">
                    <div class="titulo-box">
                        <h2 class="titulo-seccion">${escapeHtml(seccion.nombre)}</h2>
                    </div>
                    <div class="carrusel-nav">
                        <button class="carrusel-btn carrusel-prev"><i class="fa-solid fa-chevron-left"></i></button>
                        <button class="carrusel-btn carrusel-next"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                </div>
                <div class="carrusel-contenedor">
                    <div class="carrusel-track">${cardsHtml}</div>
                </div>
            </section>`;
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

    initAllCarousels();

    window.addEventListener('scroll', () => {
        if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 300) {
            cargarMasSecciones();
        }
    });
})();