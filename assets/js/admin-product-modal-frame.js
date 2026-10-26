document.querySelectorAll('[data-product-modal-close]').forEach((button) => {
    button.addEventListener('click', () => {
        window.parent.postMessage({ type: 'product-modal-close' }, window.location.origin);
    });
});
