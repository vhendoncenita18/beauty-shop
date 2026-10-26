(() => {
    const dialog = document.getElementById('admin-order-review-dialog');
    const iframe = dialog?.querySelector('iframe');
    if (!(dialog instanceof HTMLDialogElement) || !iframe || typeof dialog.showModal !== 'function') return;

    let activeOpener = null;
    document.querySelectorAll('[data-order-review]').forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            activeOpener = link;
            iframe.src = link.dataset.orderReview;
            dialog.showModal();
            dialog.querySelector('[data-order-review-close]')?.focus();
        });
    });

    const closeDialog = () => {
        if (dialog.open) dialog.close();
    };
    dialog.querySelector('[data-order-review-close]')?.addEventListener('click', closeDialog);
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) closeDialog();
    });
    dialog.addEventListener('close', () => {
        iframe.src = 'about:blank';
        activeOpener?.focus();
    });
    window.addEventListener('message', (event) => {
        if (event.origin !== window.location.origin || event.source !== iframe.contentWindow) return;
        if (event.data?.type === 'order-payment-confirmed') {
            closeDialog();
            window.location.reload();
        } else if (event.data?.type === 'order-review-close') {
            closeDialog();
        }
    });
})();
