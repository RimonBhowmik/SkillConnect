document.addEventListener('DOMContentLoaded', () => {
    const reveals = document.querySelectorAll('[data-reveal]');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });

    reveals.forEach((item, index) => {
        item.style.transitionDelay = `${index * 60}ms`;
        observer.observe(item);
    });

    const confirmLinks = document.querySelectorAll('[data-confirm]');
    confirmLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            const message = link.getAttribute('data-confirm') || 'Are you sure?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
});
