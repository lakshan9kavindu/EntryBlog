document.addEventListener('click', (event) => {
    const toggle = event.target.closest('.menu-toggle');
    if (!toggle) return;

    const navbar = toggle.closest('.navbar');
    const isOpen = navbar.classList.toggle('menu-open');
    toggle.setAttribute('aria-expanded', String(isOpen));
    toggle.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
});

document.addEventListener('DOMContentLoaded', () => {
    if (document.body.dataset.authenticated !== 'true') return;

    document.querySelectorAll('[data-hide-when-authenticated="true"]').forEach((element) => {
        element.hidden = true;
    });
});
