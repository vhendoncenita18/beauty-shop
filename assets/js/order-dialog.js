document.querySelectorAll('[data-order-dialog-open]').forEach((opener) => {
  opener.addEventListener('click', (event) => {
    const dialog = document.getElementById(opener.dataset.orderDialogOpen);
    if (!dialog || typeof dialog.showModal !== 'function') return;

    event.preventDefault();
    dialog.showModal();
    opener.setAttribute('aria-expanded', 'true');
  });
});

document.querySelectorAll('[data-order-dialog]').forEach((dialog) => {
  dialog.querySelectorAll('[data-order-dialog-close]').forEach((closeButton) => {
    closeButton.addEventListener('click', () => dialog.close());
  });

  dialog.addEventListener('click', (event) => {
    if (event.target === dialog) dialog.close();
  });

  dialog.addEventListener('close', () => {
    const opener = document.querySelector(`[data-order-dialog-open="${dialog.id}"]`);
    opener?.setAttribute('aria-expanded', 'false');
  });
});
