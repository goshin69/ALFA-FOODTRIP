(function() {
    const header = document.querySelector('header');
    if (!header) return;

    let lastScrollY = window.scrollY;
    let ticking = false;
    let hidden = false;
    const SCROLL_OFFSET = 80;
    let paddingApplied = false;

    function adjustContentPadding() {
        const headerHeight = header.offsetHeight;
        const contentElement = document.querySelector('main') || document.querySelector('.content') || document.querySelector('.page-content');
        if (contentElement) {
            contentElement.style.paddingTop = headerHeight + 'px';
        } else {
            document.body.style.paddingTop = headerHeight + 'px';
        }
        paddingApplied = true;
    }

    adjustContentPadding();
    window.addEventListener('resize', function() {
        adjustContentPadding();
        if (hidden) {
            header.classList.remove('header-hidden');
            hidden = false;
            lastScrollY = window.scrollY;
        }
    });

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
        
        if (Math.abs(delta) < 10) {
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
        hamburger.addEventListener('click', function() {
            sideMenu.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
        if (closeMenu) {
            closeMenu.addEventListener('click', function() {
                sideMenu.classList.remove('active');
                document.body.style.overflow = '';
            });
        }
        sideMenu.addEventListener('click', function(e) {
            if (e.target === sideMenu) {
                sideMenu.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    }
})();