(() => {
    const guardedForms = document.querySelectorAll('[data-confirm]');
    if (!guardedForms.length) return;

    if (typeof HTMLDialogElement === 'undefined' || !HTMLDialogElement.prototype.showModal) {
        guardedForms.forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (!window.confirm(form.dataset.confirm)) event.preventDefault();
            });
        });
        return;
    }

    const dialog = document.createElement('dialog');
    dialog.className = 'action-confirm-dialog';
    dialog.setAttribute('aria-labelledby', 'action-confirm-title');
    dialog.innerHTML = `
        <div class="action-confirm-content">
            <span class="action-confirm-mark" aria-hidden="true">!</span>
            <h2 id="action-confirm-title"></h2>
            <p class="action-confirm-message"></p>
            <div class="action-confirm-actions">
                <button class="button button-secondary action-confirm-cancel" type="button">Cancel</button>
                <button class="button action-confirm-submit" type="button">Continue</button>
            </div>
        </div>`;
    document.body.append(dialog);

    const title = dialog.querySelector('#action-confirm-title');
    const message = dialog.querySelector('.action-confirm-message');
    const cancelButton = dialog.querySelector('.action-confirm-cancel');
    const confirmButton = dialog.querySelector('.action-confirm-submit');
    let activeForm = null;
    let activeSubmitter = null;
    let previousFocus = null;

    guardedForms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed === 'true') {
                delete form.dataset.confirmed;
                return;
            }

            event.preventDefault();
            activeForm = form;
            activeSubmitter = event.submitter || null;
            previousFocus = document.activeElement;
            title.textContent = form.dataset.confirmTitle || 'Confirm this action';
            message.textContent = form.dataset.confirm;
            confirmButton.textContent = form.dataset.confirmLabel || 'Continue';
            dialog.showModal();
            cancelButton.focus();
        });
    });

    const clearDialog = () => {
        activeForm = null;
        activeSubmitter = null;
        if (previousFocus instanceof HTMLElement && previousFocus.isConnected) previousFocus.focus();
        previousFocus = null;
    };

    cancelButton.addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });
    dialog.addEventListener('close', clearDialog);
    confirmButton.addEventListener('click', () => {
        const form = activeForm;
        const submitter = activeSubmitter;
        if (!form) return dialog.close();
        form.dataset.confirmed = 'true';
        dialog.close();
        if (submitter && submitter.form === form) form.requestSubmit(submitter);
        else form.requestSubmit();
    });
})();
