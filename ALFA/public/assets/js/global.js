(function() {
    const header = document.querySelector('header');
    if (!header) return;
    let lastScrollY = window.scrollY;
    let ticking = false;
    let rafId = null;
    const SCROLL_THRESHOLD = 50;
    const DIRECTION_THRESHOLD = 20;

    function updateHeader() {
        const currentScrollY = window.scrollY;
        const delta = currentScrollY - lastScrollY;
        if (Math.abs(delta) < DIRECTION_THRESHOLD) {
            ticking = false;
            return;
        }
        if (currentScrollY > SCROLL_THRESHOLD && delta > 0) {
            header.classList.add('header-shrink');
        } else if (delta < 0 || currentScrollY <= SCROLL_THRESHOLD) {
            header.classList.remove('header-shrink');
        }
        lastScrollY = currentScrollY;
        ticking = false;
    }

    function onScroll() {
        if (!ticking) {
            rafId = requestAnimationFrame(updateHeader);
            ticking = true;
        }
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('beforeunload', () => {
        if (rafId) cancelAnimationFrame(rafId);
        window.removeEventListener('scroll', onScroll);
    });
    header.classList.remove('header-shrink');
})();