(() => {
    const overlay = document.querySelector('.loading-overlay');

    if (!overlay) {
        return;
    }

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement)
            || form.method.toLowerCase() !== 'post'
            || form.target === '_blank'
            || event.defaultPrevented) {
            return;
        }

        overlay.hidden = false;
        overlay.setAttribute('aria-hidden', 'false');
    });

    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            overlay.hidden = true;
            overlay.setAttribute('aria-hidden', 'true');
        }
    });
})();
