// vani.bundles: thêm cả bộ combo vào giỏ trong một lần (window.vani.cart.addItems — món nào lỗi thì không món nào được thêm).
document.addEventListener('submit', async (event) => {
    const form = event.target;
    const cart = window.vani?.cart;
    if (!(form instanceof HTMLFormElement) || !form.matches('[data-vani-bundle-form]') || !cart?.addItems) return;

    event.preventDefault();
    const button = form.querySelector('button[type="submit"]');
    if (!button || button.disabled) return;

    const items = Array.from(form.querySelectorAll('[data-vani-bundle-item]'), (item) => ({
        variant_id: Number(item.querySelector('select').value),
        quantity: Number(item.querySelector('input[type="hidden"]').value),
    }));
    const error = form.querySelector('[data-vani-bundle-error]');
    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
    error.hidden = true;

    try {
        const state = await cart.addItems(items);
        await cart.open(state);
    } catch (failure) {
        const message = Object.values(failure?.errors ?? {}).flat()[0] ?? failure?.message;
        if (failure?.status && message) {
            error.textContent = message;
            error.hidden = false;
        } else {
            window.location.assign(form.action);
        }
    } finally {
        button.disabled = false;
        button.removeAttribute('aria-busy');
    }
});
