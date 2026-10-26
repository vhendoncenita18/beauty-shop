(() => {
    const openers = [...document.querySelectorAll('[data-cart-drawer-open]')];
    const dialog = document.querySelector('[data-cart-drawer]');
    const closeButton = document.querySelector('[data-cart-drawer-close]');
    if (openers.length === 0 || !dialog || typeof dialog.showModal !== 'function') return;

    const storageKey = 'beauty-shop-cart-drawer-open';
    let lastOpener = openers[0];
    const openDrawer = () => {
        if (!dialog.open) dialog.showModal();
        openers.forEach((opener) => opener.setAttribute('aria-expanded', 'true'));
        closeButton?.focus();
    };

    openers.forEach((opener) => opener.addEventListener('click', (event) => {
        event.preventDefault();
        lastOpener = opener;
        openDrawer();
    }));

    closeButton?.addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });
    dialog.addEventListener('close', () => {
        openers.forEach((opener) => opener.setAttribute('aria-expanded', 'false'));
        lastOpener.focus();
    });

    dialog.addEventListener('submit', async (event) => {
        const form = event.target.closest('[data-cart-drawer-action]');
        if (!form) return;
        event.preventDefault();
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) submitButton.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            let result;
            try {
                result = await response.json();
            } catch {
                throw new Error('The bag could not be updated. Please try again.');
            }
            if (!response.ok || result.ok !== true) {
                throw new Error(result.message || 'The bag could not be updated. Try again.');
            }
            if (typeof result.redirect === 'string') {
                window.location.assign(result.redirect);
                return;
            }
            try {
                sessionStorage.setItem(storageKey, '1');
            } catch {
                // The updated cart still reloads if storage is disabled.
            }
            window.location.reload();
        } catch (error) {
            let status = dialog.querySelector('[data-cart-drawer-status]');
            if (!status) {
                status = document.createElement('p');
                status.className = 'form-status';
                status.setAttribute('role', 'alert');
                status.setAttribute('data-cart-drawer-status', '');
                dialog.querySelector('.cart-drawer-header')?.after(status);
            }
            status.textContent = error instanceof Error ? error.message : 'The bag could not be updated. Try again.';
            if (submitButton) submitButton.disabled = false;
        }
    });

    try {
        if (sessionStorage.getItem(storageKey) === '1') {
            sessionStorage.removeItem(storageKey);
            openDrawer();
        }
    } catch {
        // The drawer remains available when session storage is disabled.
    }
})();
