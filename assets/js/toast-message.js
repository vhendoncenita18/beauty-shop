(() => {
    const toast = document.querySelector('[data-toast]');
    if (!toast) return;

    const dismiss = () => {
        toast.classList.add('is-dismissing');
        window.setTimeout(() => toast.remove(), 200);
    };

    toast.querySelector('[data-toast-close]')?.addEventListener('click', dismiss);
    window.setTimeout(dismiss, toast.classList.contains('is-error') ? 7000 : 5000);
})();
