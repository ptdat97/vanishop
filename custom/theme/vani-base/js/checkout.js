export function mountCheckout() {
    const form = document.querySelector('form[data-checkout-form]');
    if (!form) return;

    document.querySelector('[data-checkout-errors]')?.focus();
    const status = form.querySelector('[data-checkout-quote-status]');
    const selectors = ['[data-checkout-summary]', '[data-checkout-shipping]', '[data-checkout-payment]', '[data-checkout-bar]'];
    const ward = form.querySelector('[data-ward-select]');
    let pending = null;
    let placing = false;

    function syncBusyState() {
        const summary = form.querySelector('[data-checkout-summary]');
        if (pending) summary?.setAttribute('aria-busy', 'true');
        else summary?.removeAttribute('aria-busy');
        form.querySelectorAll('[data-checkout-submit]').forEach((button) => {
            if (pending || placing || ward?.getAttribute('aria-busy') === 'true') button.setAttribute('aria-disabled', 'true');
            else button.removeAttribute('aria-disabled');
        });
    }

    async function requote(prepare = null) {
        pending?.abort();
        const request = new AbortController();
        pending = request;
        syncBusyState();
        if (status) {
            status.classList.add('sr-only');
            status.textContent = status.dataset.updating;
        }

        try {
            if (prepare) await prepare();
            if (request.signal.aborted) return;
            const data = new FormData(form);
            data.set('action', 'quote');
            data.delete('voucher_code');
            const response = await fetch(form.getAttribute('action'), {
                method: 'POST',
                body: data,
                credentials: 'same-origin',
                redirect: 'error',
                headers: { Accept: 'text/html', 'X-Vani-Checkout-View': 'quote' },
                signal: request.signal,
            });
            if (!response.ok) throw new Error(String(response.status));
            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            if (request.signal.aborted) return;
            const currentTotal = form.querySelector('input[name="expected_total"]');
            const quotedTotal = page.querySelector('input[name="expected_total"]');
            const replacements = selectors.map((selector) => [form.querySelector(selector), page.querySelector(selector)]);
            if (!currentTotal || !quotedTotal || replacements.some(([current, fresh]) => current && !fresh)) throw new Error('Missing checkout quote');

            const focused = document.activeElement;
            const voucher = form.querySelector('[name="voucher_code"]');
            for (const [current, fresh] of replacements) {
                if (!current) continue;
                const replacement = document.importNode(fresh, true);
                const restoreFocus = current.contains(focused);
                const nextVoucher = replacement.querySelector('[name="voucher_code"]');
                if (voucher && nextVoucher) nextVoucher.value = voucher.value;
                current.replaceWith(replacement);
                if (restoreFocus && focused.matches('input[type="radio"]')) {
                    const choice = [...replacement.querySelectorAll('input[type="radio"]')].find((input) => input.value === focused.value);
                    (choice ?? replacement).focus({ preventScroll: true });
                } else if (restoreFocus && focused.id) {
                    document.getElementById(focused.id)?.focus({ preventScroll: true });
                } else if (restoreFocus) {
                    replacement.querySelector('[data-checkout-submit], a[href="#checkout-summary"]')?.focus({ preventScroll: true });
                }
            }
            currentTotal.value = quotedTotal.value;
            if (status) status.textContent = status.dataset.updated;
            document.dispatchEvent(new CustomEvent('vani:content-updated', { detail: { element: form } }));
        } catch {
            if (!request.signal.aborted && status) {
                status.textContent = status.dataset.failed;
                status.classList.remove('sr-only');
            }
        } finally {
            if (pending === request) {
                pending = null;
                syncBusyState();
            }
        }
    }

    form.addEventListener('submit', (event) => {
        if (event.defaultPrevented || !event.submitter?.matches('[data-checkout-submit]')) return;
        if (event.submitter.getAttribute('aria-disabled') === 'true') {
            event.preventDefault();
            return;
        }
        placing = true;
        syncBusyState();
    });
    window.addEventListener('pageshow', () => {
        placing = false;
        syncBusyState();
    });
    ward?.addEventListener('vani:wards-state', syncBusyState);
    form.addEventListener('change', (event) => {
        if (event.target.matches('[data-ward-select], input[name="shipping_method"], input[name="payment_method"]')) {
            void requote();
        } else if (event.target.matches('[data-province-select]') && pending) {
            pending.abort();
            pending = null;
            syncBusyState();
            if (status) status.textContent = '';
        }
    });

    const saved = form.querySelector('[data-checkout-saved-address]');
    if (!saved) return;

    form.querySelector('[data-checkout-saved-address-wrap]').hidden = false;
    saved.addEventListener('change', () => {
        const option = saved.selectedOptions[0];
        if (!option?.dataset.fields) return;
        void requote(async () => {
            const fields = JSON.parse(option.dataset.fields);
            ['contact.full_name', 'contact.phone', 'shipping_address.street_line', 'shipping_address.province_name', 'shipping_address.ward_name'].forEach((key) => {
                const input = form.elements.namedItem(key.replace(/^(\w+)\.(\w+)$/, '$1[$2]'));
                if (input && fields[key] !== undefined) input.value = fields[key];
            });
            const province = form.querySelector('[data-province-select]');
            if (province && fields['shipping_address.province_code']) {
                province.value = fields['shipping_address.province_code'];
                await province.vaniLoadWards?.(fields['shipping_address.ward_code'] ?? '');
            }
        });
    });
}
