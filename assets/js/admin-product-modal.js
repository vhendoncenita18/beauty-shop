(() => {
    const dialog = document.getElementById('add-product-dialog');
    if (!(dialog instanceof HTMLDialogElement)) return;

    const closeDialog = () => {
        if (dialog.open) dialog.close();
    };

    document.querySelectorAll('[data-product-modal-open]').forEach((button) => {
        button.addEventListener('click', () => dialog.showModal());
    });
    dialog.querySelectorAll('[data-product-modal-close]').forEach((button) => {
        button.addEventListener('click', closeDialog);
    });
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) closeDialog();
    });
    window.addEventListener('message', (event) => {
        if (event.origin !== window.location.origin || event.source !== dialog.querySelector('iframe')?.contentWindow) return;
        if (event.data?.type === 'product-saved') {
            closeDialog();
            window.location.reload();
        } else if (event.data?.type === 'product-modal-close') {
            closeDialog();
        }
    });
})();
