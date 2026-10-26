document.querySelectorAll('.password-toggle').forEach((button) => {
    const input = document.getElementById(button.getAttribute('aria-controls'));

    if (!input) {
        return;
    }

    button.addEventListener('click', () => {
        const shouldShow = input.type === 'password';
        input.type = shouldShow ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(shouldShow));
        button.setAttribute('aria-label', shouldShow ? 'Hide password' : 'Show password');
        button.querySelector('.password-icon-visible').hidden = shouldShow;
        button.querySelector('.password-icon-hidden').hidden = !shouldShow;
    });
});
