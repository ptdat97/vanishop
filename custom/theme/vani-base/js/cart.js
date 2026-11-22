const lineUrl = (template, lineId) => String(template || '').replace('987654321', encodeURIComponent(lineId));

// Có thể có nhiều nhãn số món (header + thanh điều hướng dưới của menu di động).
export function updateCartCount(count) {
    document.querySelectorAll('[data-vani-cart-count]').forEach((badge) => {
        badge.textContent = count > 0 ? String(count) : '';
        badge.hidden = !(count > 0);
    });
}

/**
 * Sự kiện trình duyệt khi giỏ đổi (0.3.43) — plugin (đo quảng cáo…) nghe `document.addEventListener('vani:cart-updated', …)`.
 * detail: { action: 'add' | 'remove', variant_id, sku, name, quantity, unit_amount, currency }.
 */
export function dispatchCartChange(change) {
    if (change) {
        document.dispatchEvent(new CustomEvent('vani:cart-updated', { detail: change }));
    }
}

export function dispatchInlineCartChange() {
    const inlineCartChange = document.querySelector('script[data-vani-cart-change]');
    if (inlineCartChange) {
        try {
            dispatchCartChange(JSON.parse(inlineCartChange.textContent || 'null'));
        } catch {
            // dữ liệu hỏng: bỏ qua
        }
    }
}

export function createCart({ urls, request, cartPage, firstError }) {
    const offcanvas = document.querySelector('[data-vani-offcanvas-cart]');
    const offcanvasBody = offcanvas?.querySelector('[data-vani-mini-cart-body]');
    let cartOpener = null;

    /** Áp trạng thái giỏ từ server: nhãn số món, nội dung giỏ mini, sự kiện cho plugin. */
    function applyCart(state) {
        updateCartCount(state.count ?? 0);
        if (offcanvasBody && typeof state.html === 'string') {
            offcanvasBody.innerHTML = state.html; // HTML do Blade render (đã escape) — không chứa dữ liệu thô từ client.
            offcanvasBody.setAttribute('aria-busy', 'false');
            document.dispatchEvent(new CustomEvent('vani:content-updated', { detail: { element: offcanvasBody } }));
        }
        if (cartPage && typeof state.page_html === 'string') {
            // Giữ chỗ focus (ô số lượng, ô chọn…) sau khi thay HTML.
            const focusedId = document.activeElement && cartPage.contains(document.activeElement) ? document.activeElement.id : '';
            cartPage.innerHTML = state.page_html; // HTML do Blade render (đã escape).
            if (focusedId) document.getElementById(focusedId)?.focus();
            document.dispatchEvent(new CustomEvent('vani:content-updated', { detail: { element: cartPage } }));
        }
        dispatchCartChange(state.change);
        document.dispatchEvent(new CustomEvent('vani:cart', { detail: state }));
        return state;
    }

    function showCartMessage(text) {
        const box = offcanvas?.querySelector('[data-vani-cart-message]');
        if (!box) return;
        box.textContent = text || '';
        box.classList.toggle('hidden', !text);
    }

    const cart = {
        get: () => request('GET', urls.cart).then(applyCart),
        add: (variantId, quantity = 1, options = {}, extra = {}) => request('POST', urls.cart_add, { variant_id: variantId, quantity, options, ...extra }).then(applyCart),
        /** Thêm nhiều món một lần (`[{variant_id, quantity}]`): một món lỗi thì không món nào được thêm. */
        addItems: (items) => request('POST', urls.cart_add, { items }).then(applyCart),
        update: (lineId, quantity) => request('POST', lineUrl(urls.cart_line, lineId), { quantity }).then(applyCart),
        remove: (lineId) => request('POST', lineUrl(urls.cart_remove, lineId)).then(applyCart),
        select: (lineIds) => request('POST', urls.cart_select, { lines: lineIds }).then(applyCart),
        applyVoucher: (code) => request('POST', urls.cart_voucher, { code }).then(applyCart),
        removeVoucher: () => request('POST', urls.cart_voucher, { code: '' }).then(applyCart),
        /** Mở giỏ mini; `state` (kết quả add…) có sẵn thì không tải lại. */
        open(state = null) {
            if (!offcanvas) {
                window.location.href = urls.cart;
                return Promise.resolve(null);
            }
            if (!offcanvas.open) {
                cartOpener = document.activeElement;
                offcanvas.showModal();
            }
            showCartMessage(state?.message ?? '');
            if (state) return Promise.resolve(applyCart(state));
            offcanvasBody?.setAttribute('aria-busy', 'true');
            return cart.get().catch(() => {
                window.location.href = urls.cart;
            });
        },
        close() {
            offcanvas?.close();
        },
    };

    function mountCartPage() {
        // Giỏ: đổi số lượng thì gửi luôn.
        // Trang giỏ đầy đủ (0.3.94): sửa số lượng, xoá dòng, chọn dòng, mã giảm giá qua window.vani.cart — server trả `page_html`,
        // thay vùng [data-vani-cart-page] (không tải lại trang). Gắn sự kiện ở vùng bao ngoài vì nội dung bên trong được thay.
        if (cartPage) {
            const run = async (action) => {
                const error = cartPage.querySelector('[data-vani-cart-error]');
                if (error) error.hidden = true;
                cartPage.setAttribute('aria-busy', 'true');
                try {
                    const state = await action();
                    const status = document.querySelector('[data-vani-flash="status"]');
                    if (status && state?.message) {
                        status.textContent = state.message;
                        status.hidden = false;
                    }
                } catch (failure) {
                    // Tải lại trạng thái giỏ (số lượng trên ô có thể đã đổi tay) rồi báo lỗi tại chỗ.
                    await cart.get().catch(() => {});
                    const field = failure.errors?.code ? 'code' : Object.keys(failure.errors || {})[0];
                    const target = field === 'code' ? cartPage.querySelector('[data-cart-voucher-error]') : cartPage.querySelector('[data-vani-cart-error]');
                    if (target) {
                        target.textContent = field ? firstError(failure, field) : failure.message;
                        target.hidden = false;
                    }
                } finally {
                    cartPage.setAttribute('aria-busy', 'false');
                }
            };
            const selectedLines = () => [...cartPage.querySelectorAll('[data-select-line]')].filter((input) => input.checked && !input.disabled).map((input) => Number(input.dataset.line));

            cartPage.addEventListener('change', (event) => {
                const target = event.target;
                if (target.matches('[data-autosubmit][data-line]')) {
                    const quantity = Math.max(0, Number.parseInt(target.value, 10) || 0);
                    run(() => (quantity === 0 ? cart.remove(Number(target.dataset.line)) : cart.update(Number(target.dataset.line), quantity)));
                } else if (target.matches('[data-select-all]')) {
                    cartPage.querySelectorAll('[data-select-line]').forEach((input) => {
                        if (!input.disabled) input.checked = target.checked;
                    });
                    run(() => cart.select(selectedLines()));
                } else if (target.matches('[data-select-line]')) {
                    run(() => cart.select(selectedLines()));
                }
            });
            cartPage.addEventListener('submit', (event) => {
                const form = event.target;
                if (form.matches('[data-cart-remove-form]')) {
                    event.preventDefault();
                    run(() => cart.remove(Number(form.dataset.line)));
                } else if (form.matches('[data-cart-update-form]')) {
                    event.preventDefault();
                    const quantity = Math.max(0, Number.parseInt(String(new FormData(form).get('quantity') ?? ''), 10) || 0);
                    run(() => (quantity === 0 ? cart.remove(Number(form.dataset.line)) : cart.update(Number(form.dataset.line), quantity)));
                } else if (form.matches('[data-cart-voucher-form]')) {
                    event.preventDefault();
                    const code = String(new FormData(form).get('code') ?? '').trim();
                    run(() => (code === '' ? cart.removeVoucher() : cart.applyVoucher(code)));
                } else if (form.id === 'cart-selection') {
                    event.preventDefault();
                    run(() => cart.select(selectedLines()));
                }
            });
        }
    }

    function mountMiniCart() {
        // ── Giỏ mini offcanvas (0.3.93) ─────────────────────────────────────────────────────────────────────────────────────
        if (offcanvas) {
            document.querySelectorAll('[data-vani-cart-open]').forEach((link) =>
                link.addEventListener('click', (event) => {
                    event.preventDefault();
                    cart.open();
                }),
            );
            offcanvas.addEventListener('close', () => {
                showCartMessage('');
                cartOpener?.focus?.();
            });
            // Bấm nền tối (ngoài khung) → đóng.
            offcanvas.addEventListener('click', (event) => {
                if (event.target === offcanvas) cart.close();
            });

            const busy = async (action) => {
                offcanvasBody?.setAttribute('aria-busy', 'true');
                const controls = offcanvasBody?.querySelectorAll('button:enabled, input:enabled') ?? [];
                controls.forEach((control) => { control.disabled = true; });
                try {
                    const state = await action();
                    showCartMessage(state?.message ?? '');
                } catch (error) {
                    showCartMessage('');
                    // Lỗi theo trường (mã giảm giá…) hiện tại chỗ; lỗi khác: tải lại nội dung giỏ rồi báo.
                    if (error.errors?.code) {
                        await cart.get().catch(() => {});
                        const target = offcanvasBody?.querySelector('[data-cart-voucher-error]');
                        if (target) {
                            target.textContent = firstError(error, 'code');
                            target.hidden = false;
                        }
                        return;
                    }
                    await cart.get().catch(() => {});
                    showCartMessage(firstError(error, 'quantity'));
                } finally {
                    offcanvasBody?.setAttribute('aria-busy', 'false');
                    controls.forEach((control) => { control.disabled = false; });
                }
            };

            offcanvas.addEventListener('click', (event) => {
                const quantity = event.target.closest('[data-cart-quantity]');
                const remove = event.target.closest('[data-cart-remove]');
                if (event.target.closest('[data-cart-close]')) {
                    cart.close();
                } else if (quantity) {
                    busy(() => cart.update(Number(quantity.dataset.line), Number(quantity.dataset.quantity)));
                } else if (remove) {
                    busy(() => cart.remove(Number(remove.dataset.line)));
                }
            });
            offcanvas.addEventListener('submit', (event) => {
                const form = event.target.closest('form[data-cart-voucher-form]');
                if (!form) return;
                event.preventDefault();
                const code = String(new FormData(form).get('code') ?? '').trim();
                busy(() => (code === '' ? cart.removeVoucher() : cart.applyVoucher(code)));
            });
        }
    }

    function mountProductCart() {
        document.querySelectorAll('form[data-variant-form]').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                if (!urls.cart_add) return;
                event.preventDefault();
                if (form.dataset.vaniSubmitting) return;
                form.dataset.vaniSubmitting = '1';
                const submitter = event.submitter;
                const data = new FormData(form);
                if (submitter?.name) data.append(submitter.name, submitter.value);
                const error = form.querySelector('[data-vani-error-for="variant_id"]');
                if (error) error.hidden = true;
                submitter?.setAttribute('aria-busy', 'true');
                try {
                    const state = await request('POST', urls.cart_add, data);
                    if (state.redirect) {
                        window.location.href = state.redirect;
                        return;
                    }
                    cart.open(state);
                } catch (failure) {
                    if (failure.status === 422 && error) {
                        error.textContent = firstError(failure, Object.keys(failure.errors)[0] ?? 'variant_id');
                        error.hidden = false;
                        return;
                    }
                    // Khi mất phản hồi, mở giỏ để kiểm tra vì máy chủ có thể đã thêm hàng.
                    window.location.assign(urls.cart);
                } finally {
                    delete form.dataset.vaniSubmitting;
                    submitter?.removeAttribute('aria-busy');
                }
            });
        });
    }

    return { cart, mountCartPage, mountMiniCart, mountProductCart };
}
