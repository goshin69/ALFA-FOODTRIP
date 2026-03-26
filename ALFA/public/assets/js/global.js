(function() {
    const header = document.querySelector('header');
    if (!header) return;

    function adjustContentPadding() {
        const headerHeight = header.offsetHeight;
        const contentElement = document.querySelector('main') || document.querySelector('.content') || document.querySelector('.page-content');
        if (contentElement) {
            contentElement.style.paddingTop = headerHeight + 'px';
        } else {
            document.body.style.paddingTop = headerHeight + 'px';
        }
    }

    adjustContentPadding();
    window.addEventListener('resize', adjustContentPadding);

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
    
    if (hamburger && sideMenu) {
        hamburger.addEventListener('click', () => {
            sideMenu.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
        if (closeMenu) {
            closeMenu.addEventListener('click', () => {
                sideMenu.classList.remove('active');
                document.body.style.overflow = '';
            });
        }
        sideMenu.addEventListener('click', (e) => {
            if (e.target === sideMenu) {
                sideMenu.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
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
    const themeBtn = document.getElementById('theme-switch');
    if (themeBtn) {
        themeBtn.addEventListener('click', () => {
            const isDark = document.body.classList.toggle('dark-theme');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        });
    }

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

    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
            fetch('api/logout.php', { method: 'POST', credentials: 'include' })
                .then(() => location.reload());
        });
    }
})();