// vani.bundles: thêm cả bộ combo vào giỏ trong một lần (window.vani.cart.addItems — món nào lỗi thì không món nào được thêm).
(() => {
    for (const form of document.querySelectorAll('form[data-vani-bundle-form]')) {
        if (form.dataset.ready) continue;
        form.dataset.ready = '1';
        const error = form.querySelector('[data-vani-bundle-error]');
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const cart = window.vani?.cart;
            if (!cart?.addItems || form.dataset.busy) return;
            const selects = [...form.querySelectorAll('select[data-quantity]')];
            const missing = selects.find((select) => select.value === '');
            if (missing) {
                missing.focus();
                missing.reportValidity();
                return;
            }
            const button = form.querySelector('button[type="submit"]');
            form.dataset.busy = '1';
            button?.setAttribute('aria-busy', 'true');
            if (error) error.hidden = true;
            try {
                const state = await cart.addItems(selects.map((select) => ({ variant_id: Number(select.value), quantity: Number(select.dataset.quantity) || 1 })));
                cart.open(state);
            } catch (failure) {
                const errors = failure?.errors ?? {};
                const message = Object.values(errors).flat()[0] ?? failure?.message;
                if (error && message) {
                    error.textContent = message;
                    error.hidden = false;
                } else if (window.vani?.urls?.cart) {
                    window.location.assign(window.vani.urls.cart);
                }
            } finally {
                delete form.dataset.busy;
                button?.removeAttribute('aria-busy');
            }
        });
    }
})();
