(() => {
    const form = document.querySelector('[data-product-offer-form]');
    const select = form?.querySelector('[data-offer-type]');
    if (!form || !select) return;

    const updateFields = () => {
        form.querySelectorAll('[data-offer-value-fields]').forEach((group) => {
            const active = group.dataset.offerValueFields === select.value;
            group.hidden = !active;
            group.querySelectorAll('input').forEach((input) => {
                input.disabled = !active;
                input.required = active;
            });
        });
    };

    select.addEventListener('change', updateFields);
    updateFields();
})();
