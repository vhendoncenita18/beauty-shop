document.querySelectorAll('[data-quantity-stepper]').forEach((stepper) => {
  const input = stepper.querySelector('[data-quantity-input]');
  const output = stepper.querySelector('[data-quantity-output]');
  const decrease = stepper.querySelector('[data-quantity-decrease]');
  const increase = stepper.querySelector('[data-quantity-increase]');

  if (!input || !output || !decrease || !increase) return;

  const updateQuantity = (amount) => {
    const quantity = Math.min(99, Math.max(1, Number(input.value) + amount));
    input.value = String(quantity);
    output.textContent = String(quantity);
    decrease.disabled = quantity === 1;
    increase.disabled = quantity === 99;
  };

  decrease.addEventListener('click', () => updateQuantity(-1));
  increase.addEventListener('click', () => updateQuantity(1));
});
