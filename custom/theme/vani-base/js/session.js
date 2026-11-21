export function createSession({ urls, cartPage, t }) {
    let csrfToken = null;
    let sessionLoaded;
    const sessionReady = new Promise((resolve) => {
        sessionLoaded = resolve;
    });

    /** Token CSRF: có sẵn khi trang không cache; trang cache CDN chờ /_vani/phien (tối đa 3 giây), không có thì tự gọi. */
    async function csrf(refresh = false) {
        if (csrfToken && !refresh) return csrfToken;
        if (!refresh) await Promise.race([sessionReady, new Promise((resolve) => setTimeout(resolve, 3000))]);
        if ((!csrfToken || refresh) && urls.session) {
            const session = await fetch(urls.session, { credentials: 'same-origin', headers: { Accept: 'application/json' } }).then((response) => (response.ok ? response.json() : null));
            csrfToken = session?.csrf_token ?? csrfToken;
        }
        return csrfToken ?? '';
    }

    async function request(method, url, data = null, retried = false) {
        const headers = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
        if (cartPage) headers['X-Vani-Cart-View'] = 'page';
        let body;
        if (method !== 'GET') {
            headers['X-CSRF-TOKEN'] = await csrf();
            if (data instanceof FormData) {
                body = data;
            } else {
                headers['Content-Type'] = 'application/json';
                body = JSON.stringify(data ?? {});
            }
        }
        const response = await fetch(url, { method, headers, body, credentials: 'same-origin' });
        // Phiên hết hạn (419): lấy token mới, thử lại một lần.
        if (response.status === 419 && !retried) {
            await csrf(true);
            return request(method, url, data, true);
        }
        const json = await response.json().catch(() => ({}));
        if (!response.ok) {
            const error = new Error(json.message || json.error?.message || t('request_failed'));
            error.status = response.status;
            error.errors = json.errors || {};
            error.code = json.error?.code ?? null;
            throw error;
        }
        if (json.csrf_token) csrfToken = json.csrf_token;
        return json;
    }

    const auth = {
        login: (email, password) => request('POST', urls.login_authenticate, { email, password }),
        logout: () => request('POST', urls.logout),
    };

    /**
     * Phần riêng của khách (storefront §5): không CDN thì layout nhúng sẵn `script[data-vani-session-state]`; trang cache CDN
     * lấy từ /_vani/phien (thêm flash + cart_change). Cùng một hàm áp dụng → hai chế độ hiển thị như nhau.
     */
    function applySession(session, updateCartCount, dispatchCartChange) {
        if (!session) return;
        // Cho plugin (0.3.76): đọc `window.vaniSession` hoặc nghe `vani:session` — signed_in, csrf_token, cart…
        window.vaniSession = session;
        if (session.csrf_token) csrfToken = session.csrf_token;
        sessionLoaded(session);
        document.dispatchEvent(new CustomEvent('vani:session', { detail: session }));
        dispatchCartChange(session.cart_change);
        document.querySelectorAll('[data-vani-account]').forEach((account) => {
            account.setAttribute('aria-label', session.account.label);
            account.setAttribute('title', session.account.label);
            account.setAttribute('href', session.account.url);
        });
        if (session.cart.count > 0) updateCartCount(session.cart.count);
        const status = document.querySelector('[data-vani-flash="status"]');
        if (status && session.flash?.status) {
            status.textContent = session.flash.status;
            status.hidden = false;
        }
        // Cửa hàng ẩn giá với khách chưa đăng nhập: HTML cache luôn là bản "Đăng nhập để xem giá" → điền giá cho khách đã đăng nhập.
        const hiddenPrices = [...document.querySelectorAll('[data-vani-price-for]')];
        if (session.signed_in && hiddenPrices.length && session.prices_url) {
            const query = new URLSearchParams([...new Set(hiddenPrices.map((el) => el.dataset.vaniPriceFor))].map((id) => ['style_ids[]', id]));
            // Storefront API bằng phiên cùng nguồn (ADR-044): trình duyệt gửi Sec-Fetch-Site: same-origin.
            fetch(`${session.prices_url}?${query}`, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
                .then((response) => (response.ok ? response.json() : null))
                .then((json) => {
                    const data = json?.data;
                    hiddenPrices.forEach((el) => {
                        const price = data?.products?.[el.dataset.vaniPriceFor];
                        if (!price) return;
                        const span = document.createElement('span');
                        span.className = 'font-semibold text-primary';
                        span.textContent = price.max.amount !== price.min.amount ? `${price.min.formatted} – ${price.max.formatted}` : price.min.formatted;
                        el.replaceWith(span);
                    });
                })
                .catch(() => {});
        }
        const memberPrice = document.querySelector('[data-vani-member-price]');
        const variantIds = [...document.querySelectorAll('input[name="variant_id"], select[name="variant_id"] option')].map((input) => input.value);
        if (session.signed_in && memberPrice && variantIds.length) {
            const query = new URLSearchParams(variantIds.map((id) => ['variant_ids[]', id]));
            fetch(`${memberPrice.dataset.route}?${query}`, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
                .then((response) => (response.ok ? response.json() : null))
                .then((json) => {
                    const data = json?.data;
                    const prices = Object.values(data?.prices || {});
                    if (!prices.length) return;
                    const lowest = prices.reduce((min, price) => (price.amount < min.amount ? price : min));
                    memberPrice.textContent = t(prices.length < variantIds.length ? 'member_price_from' : 'member_price', { group: data.group ?? t('member'), price: lowest.formatted });
                    memberPrice.hidden = false;
                })
                .catch(() => {});
        }
        Object.entries(session.flash?.errors || {}).forEach(([field, message]) => {
            const target = document.querySelector(`[data-vani-error-for="${field}"]`) || document.querySelector('[data-vani-error-for="business"]');
            if (target && message) {
                target.textContent = message;
                target.hidden = false;
            }
        });
    }

    function mount({ updateCartCount, dispatchCartChange }) {
        const apply = (session) => applySession(session, updateCartCount, dispatchCartChange);
        const inlineSession = document.querySelector('script[data-vani-session-state]');
        if (inlineSession) {
            try {
                apply(JSON.parse(inlineSession.textContent || 'null'));
            } catch {
                // dữ liệu hỏng: bỏ qua
            }
        }

        const sessionUrl = document.documentElement.dataset.vaniSession;
        if (sessionUrl) {
            fetch(sessionUrl, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
                .then((response) => (response.ok ? response.json() : null))
                .then(apply)
                .catch(() => {});
        }
    }

    function mountLoginForm(firstError) {
        // Đăng nhập email + mật khẩu không tải lại trang: thành công → về trang trước đó (hoặc tài khoản).
        const loginForm = document.querySelector('form[data-vani-login]');
        if (loginForm) {
            const errorBox = loginForm.querySelector('[data-vani-form-error]');
            loginForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                if (errorBox) errorBox.hidden = true;
                const data = new FormData(loginForm);
                try {
                    const result = await auth.login(String(data.get('email') ?? ''), String(data.get('password') ?? ''));
                    window.location.href = result.redirect;
                } catch (error) {
                    if (errorBox) {
                        errorBox.textContent = firstError(error, 'business') || firstError(error, 'email') || firstError(error, 'password');
                        errorBox.hidden = !errorBox.textContent;
                    }
                }
            });
        }
    }

    return { request, auth, mount, mountLoginForm };
}
