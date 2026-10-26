document.querySelectorAll('[data-cart-animation]').forEach((form) => {
  form.addEventListener('submit', (event) => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const image = form.closest('.product-card, .product-detail-card')?.querySelector('img');
    const target = document.querySelector('.header-bag-link, .cart-icon-link');
    if (!target) return;

    event.preventDefault();
    const bounds = image?.getBoundingClientRect() ?? form.getBoundingClientRect();
    const destination = target.getBoundingClientRect();
    const item = document.createElement('span');
    item.className = 'cart-fly-item';
    item.setAttribute('aria-hidden', 'true');
    if (image) {
      const productImage = image.cloneNode();
      productImage.removeAttribute('loading');
      item.append(productImage);
    } else {
      item.textContent = '✦';
    }
    item.style.left = `${bounds.left + bounds.width / 2}px`;
    item.style.top = `${bounds.top + bounds.height / 2}px`;
    document.body.append(item);
    requestAnimationFrame(() => {
      item.style.transform = `translate(${destination.left - bounds.left}px, ${destination.top - bounds.top}px) scale(.2)`;
      item.style.opacity = '0';
    });
    window.setTimeout(() => form.submit(), 450);
  });
});
