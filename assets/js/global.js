(function() {
    const header = document.querySelector('header');
    if (!header) return;

    let ghost = document.querySelector('.header-ghost');
    if (!ghost) {
        ghost = document.createElement('div');
        ghost.className = 'header-ghost';
        header.insertAdjacentElement('afterend', ghost);
    }

    function updateGhostAndPadding() {
        const headerHeight = header.offsetHeight;
        ghost.style.height = headerHeight + 'px';
        
        const contentElement = document.querySelector('main') || 
                              document.querySelector('.tendencias-main') ||
                              document.querySelector('.content') || 
                              document.querySelector('.page-content') ||
                              document.querySelector('#app')||
                              document.querySelector('.buscar-main');
        
        if (contentElement) {
            contentElement.style.paddingTop = '0px';
        } else {
            document.body.style.paddingTop = '0px';
        }
    }

    const resizeObserver = new ResizeObserver(() => {
        updateGhostAndPadding();
    });
    resizeObserver.observe(header);

    updateGhostAndPadding();
    
    window.addEventListener('resize', () => {
        updateGhostAndPadding();
    });
    window.addEventListener('load', updateGhostAndPadding); 

    let lastScrollY = window.scrollY;
    let ticking = false;
    let hidden = false;
    const SCROLL_OFFSET = 80;
    const TOLERANCE = 10;

    function updateHeader() {
        const currentScrollY = window.scrollY;
        const delta = currentScrollY - lastScrollY;
        
        if (currentScrollY <= SCROLL_OFFSET) {
            if (hidden) {
                header.classList.remove('header-hidden');
                hidden = false;
            }
            lastScrollY = currentScrollY;
            ticking = false;
            return;
        }
        
        if (Math.abs(delta) < TOLERANCE) {
            ticking = false;
            return;
        }
        
        if (delta > 0 && currentScrollY > SCROLL_OFFSET) {
            if (!hidden) {
                header.classList.add('header-hidden');
                hidden = true;
            }
        } else if (delta < 0) {
            if (hidden) {
                header.classList.remove('header-hidden');
                hidden = false;
            }
        }
        
        lastScrollY = currentScrollY;
        ticking = false;
    }
    
    function onScroll() {
        if (!ticking) {
            requestAnimationFrame(updateHeader);
            ticking = true;
        }
    }
    
    window.addEventListener('scroll', onScroll, { passive: true });
    
    const hamburger = document.getElementById('menu-hamburger');
    const sideMenu = document.getElementById('side-menu');
    const closeMenu = document.getElementById('close-menu');
    const overlay = document.getElementById('menu-overlay');
    
    function closeSideMenu() {
        sideMenu.classList.remove('active');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
    const closeMenuButton = document.getElementById('close-menu-button');
    if (closeMenuButton) {
        closeMenuButton.addEventListener('click', (e) => {
            e.preventDefault();
            closeSideMenu();
        });
    }
    
    if (hamburger && sideMenu) {
        hamburger.addEventListener('click', () => {
            sideMenu.classList.add('active');
            if (overlay) overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
        if (closeMenu) {
            closeMenu.addEventListener('click', closeSideMenu);
        }
        if (overlay) {
            overlay.addEventListener('click', closeSideMenu);
        }
    }

    const trigger = document.getElementById('profile-trigger');
    const dropdown = document.getElementById('profile-dropdown');
    if (trigger && dropdown) {
        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.classList.toggle('open');
        });
        document.addEventListener('click', (e) => {
            if (!trigger.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.remove('open');
            }
        });
    }

    function loadTheme() {
        const theme = localStorage.getItem('theme') || 'light';
        if (theme === 'dark') {
            document.body.classList.add('dark-theme');
        } else {
            document.body.classList.remove('dark-theme');
        }
    }
    loadTheme();

    const themeSwitches = document.querySelectorAll('.theme-switch');
    themeSwitches.forEach(btn => {
        btn.addEventListener('click', () => {
            const isDark = document.body.classList.toggle('dark-theme');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        });
    });

    const translations = {
        es: {
            "Inicio": "Inicio",
            "Tendencia": "Tendencia",
            "Videos": "Videos",
            "Notificación": "Notificación",
            "Crear Receta": "Crear Receta",
            "Configuración": "Configuración",
            "Buscar recetas...": "Buscar recetas...",
            "Mi perfil": "Mi perfil",
            "Claro / Oscuro": "Claro / Oscuro",
            "Idioma": "Idioma",
            "Cerrar sesión": "Cerrar sesión"
        },
        en: {
            "Inicio": "Home",
            "Tendencia": "Trending",
            "Videos": "Videos",
            "Notificación": "Notifications",
            "Crear Receta": "Create Recipe",
            "Configuración": "Settings",
            "Buscar recetas...": "Search recipes...",
            "Mi perfil": "My profile",
            "Claro / Oscuro": "Light / Dark",
            "Idioma": "Language",
            "Cerrar sesión": "Log out"
        }
    };
    let currentLang = localStorage.getItem('lang') || 'es';
    function applyLanguage() {
        document.querySelectorAll('[data-i18n]').forEach(el => {
            const key = el.getAttribute('data-i18n');
            if (translations[currentLang][key]) {
                if (el.tagName === 'INPUT' && el.placeholder !== undefined) {
                    el.placeholder = translations[currentLang][key];
                } else {
                    el.textContent = translations[currentLang][key];
                }
            }
        });
        const langSpan = document.querySelector('.current-lang');
        if (langSpan) langSpan.textContent = currentLang === 'es' ? 'Español' : 'English';
    }
    applyLanguage();
    const langBtn = document.getElementById('language-switch');
    if (langBtn) {
        langBtn.addEventListener('click', () => {
            currentLang = currentLang === 'es' ? 'en' : 'es';
            localStorage.setItem('lang', currentLang);
            applyLanguage();
        });
    }

    const logoutBtns = document.querySelectorAll('.logout-btn');
    logoutBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            fetch('api/logout.php', { method: 'POST', credentials: 'include' })
                .then(() => location.reload());
        });
    });
})();

document.addEventListener('DOMContentLoaded', function() {
    const notifWrapper = document.getElementById('notif-wrapper');
    const notifSideTrigger = document.getElementById('notif-side-trigger');
    const badge = document.getElementById('notif-badge');
    const dropdown = document.getElementById('notif-dropdown');
    const overlay = document.getElementById('notif-overlay');

    let modalOverlay = null;
    let modalContainer = null;

    function isMobile() {
        return window.innerWidth <= 768;
    }

    function crearModal() {
        if (modalContainer) return modalContainer;
        modalOverlay = document.createElement('div');
        modalOverlay.className = 'notif-modal-overlay';
        document.body.appendChild(modalOverlay);
        modalContainer = document.createElement('div');
        modalContainer.className = 'notif-modal';
        modalContainer.innerHTML = `
            <div class="notif-modal-header">
                <span>Notificaciones</span>
                <button>&times;</button>
            </div>
            <div class="notif-modal-content"></div>
        `;
        document.body.appendChild(modalContainer);
        const closeBtn = modalContainer.querySelector('button');
        closeBtn.addEventListener('click', cerrarModal);
        modalOverlay.addEventListener('click', cerrarModal);
        return modalContainer;
    }

    function cargarNotificacionesEnModal() {
        const modal = crearModal();
        const contentDiv = modal.querySelector('.notif-modal-content');
        fetch('api/notif.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=list'
        })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) return;
            const total = data.no_leidas;
            if (total > 0) {
                badge.textContent = total;
                badge.style.display = 'inline';
            } else {
                badge.style.display = 'none';
            }
            contentDiv.innerHTML = '';
            if (data.notificaciones.length === 0) {
                contentDiv.innerHTML = '<div class="notif-item">No hay notificaciones</div>';
                return;
            }
            data.notificaciones.forEach(n => {
                const div = document.createElement('div');
                div.className = 'notif-item' + (n.leida == 0 ? ' no-leida' : '');
                div.innerHTML = n.mensaje + '<small>' + n.fecha + '</small>';
                div.addEventListener('click', () => {
                    fetch('api/notif.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'action=read&id=' + n.id
                    }).then(() => cargarNotificacionesEnModal());
                });
                contentDiv.appendChild(div);
            });
        });
    }

    function abrirModal() {
        const modal = crearModal();
        cargarNotificacionesEnModal();
        modal.classList.add('show');
        modalOverlay.classList.add('show');
    }

    function cerrarModal() {
        if (modalContainer) modalContainer.classList.remove('show');
        if (modalOverlay) modalOverlay.classList.remove('show');
    }

    function cargarNotificacionesEscritorio() {
        fetch('api/notif.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=list'
        })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) return;
            const total = data.no_leidas;
            if (total > 0) badge.textContent = total;
            else badge.style.display = 'none';
            dropdown.innerHTML = '';
            if (data.notificaciones.length === 0) {
                dropdown.innerHTML = '<div class="notif-item">No hay notificaciones</div>';
                return;
            }
            data.notificaciones.forEach(n => {
                const div = document.createElement('div');
                div.className = 'notif-item' + (n.leida == 0 ? ' no-leida' : '');
                div.innerHTML = n.mensaje + '<small>' + n.fecha + '</small>';
                div.addEventListener('click', () => {
                    fetch('api/notif.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'action=read&id=' + n.id
                    }).then(() => cargarNotificacionesEscritorio());
                });
                dropdown.appendChild(div);
            });
        });
    }

    function mostrarDropdown() {
        if (isMobile()) {
            abrirModal();
        } else {
            cargarNotificacionesEscritorio();
            dropdown.classList.add('show');
        }
    }

    function ocultarDropdown() {
        if (isMobile()) {
            cerrarModal();
        } else {
            dropdown.classList.remove('show');
        }
    }
    
    
    // Toggle para el menú de navegación móvil (header-nav)
    const hamburgerBtn = document.querySelector('.hamburger');
    const headerNav = document.querySelector('.header-nav');
    if (hamburgerBtn && headerNav) {
        hamburgerBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            headerNav.classList.toggle('open');
        });
        // Cerrar menú al hacer clic en un enlace
        headerNav.querySelectorAll('.nav-item').forEach(link => {
            link.addEventListener('click', () => headerNav.classList.remove('open'));
        });
        // Cerrar al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!headerNav.contains(e.target) && !hamburgerBtn.contains(e.target)) {
                headerNav.classList.remove('open');
            }
        });
    }


    if (notifWrapper) {
        notifWrapper.addEventListener('click', function(e) {
            e.stopPropagation();
            if (!isMobile()) {
                if (dropdown.classList.contains('show')) {
                    ocultarDropdown();
                } else {
                    mostrarDropdown();
                }
            }
        });
    }

    if (notifSideTrigger) {
        notifSideTrigger.addEventListener('click', function(e) {
            e.preventDefault();
            const sideMenu = document.getElementById('side-menu');
            const menuOverlay = document.getElementById('menu-overlay');
            if (sideMenu && sideMenu.classList.contains('active')) {
                sideMenu.classList.remove('active');
                if (menuOverlay) menuOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }
            if ((isMobile() && modalContainer && modalContainer.classList.contains('show')) ||
                (!isMobile() && dropdown.classList.contains('show'))) {
                ocultarDropdown();
            } else {
                mostrarDropdown();
            }
        });
    }

    document.addEventListener('click', function(e) {
        if (!isMobile()) {
            if (!dropdown.contains(e.target) && e.target !== notifWrapper && !notifWrapper?.contains(e.target) && e.target !== notifSideTrigger && !notifSideTrigger?.contains(e.target)) {
                ocultarDropdown();
            }
        }
    });

    if (overlay) {
        overlay.addEventListener('click', ocultarDropdown);
    }

    fetch('api/notif.php', { method: 'POST', body: 'action=list' })
        .then(r => r.json())
        .then(data => {
            if (data.ok && data.no_leidas > 0) {
                badge.textContent = data.no_leidas;
                badge.style.display = 'inline';
            }
        });
        
});