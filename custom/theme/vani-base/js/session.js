export function createSession({ urls, cartPage, t }) {
    let csrfToken = null;
    /** Phiên đã áp dụng gần nhất: vùng DOM thay sau (`vani:content-updated`) dùng lại để điền phần riêng của khách. */
    let current = null;
    let sessionLoaded;
    const sessionReady = new Promise((resolve) => {
        sessionLoaded = resolve;
    });
    /** Áp dụng phiên (đặt ở `mount`); trước đó chỉ giữ token CSRF. */
    let applyLoaded = (session) => {
        if (session?.csrf_token) csrfToken = session.csrf_token;
    };
    /** Request /_vani/phien đang chạy: mọi nơi cần phiên (mở trang, token CSRF, trang khôi phục) dùng chung một request. */
    let inflight = null;

    /**
     * Lấy phần riêng của khách từ /_vani/phien rồi áp dụng. Dùng chung request đang chạy; lỗi mạng hoặc lỗi server thì thử
     * lại một lần sau 1 giây, vẫn lỗi thì giữ trạng thái đang hiện (không đoán). Phiên có flash "đọc một lần" nên luôn áp
     * dụng kết quả, không gọi riêng chỉ để lấy token.
     */
    function loadSession() {
        if (inflight) return inflight;
        const url = document.documentElement.dataset.vaniSession || urls.session;
        if (!url) return Promise.resolve(null);
        const attempt = () =>
            fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } }).then((response) => {
                if (response.status >= 500) throw new Error(`session ${response.status}`);
                return response.ok ? response.json() : null;
            });
        inflight = attempt()
            .catch(() => new Promise((resolve) => setTimeout(resolve, 1000)).then(attempt))
            .catch(() => null)
            .then((session) => {
                inflight = null;
                if (session) applyLoaded(session);
                return session;
            });
        return inflight;
    }

    /** Token CSRF: có sẵn khi trang không cache; trang cache chờ /_vani/phien (tối đa 3 giây), không có thì tự lấy. */
    async function csrf(refresh = false) {
        if (csrfToken && !refresh) return csrfToken;
        if (!refresh) await Promise.race([sessionReady, new Promise((resolve) => setTimeout(resolve, 3000))]);
        if (!csrfToken || refresh) await loadSession();
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
        dispatchCartChange(session.cart_change, session.cart_changes);
        document.querySelectorAll('[data-vani-account]').forEach((account) => {
            account.setAttribute('aria-label', session.account.label);
            account.setAttribute('title', session.account.label);
            account.setAttribute('href', session.account.url);
        });
        // Luôn áp dụng (kể cả 0): trang khôi phục từ bộ nhớ trình duyệt có thể đang hiện số món cũ.
        updateCartCount(session.cart?.count ?? 0);
        const status = document.querySelector('[data-vani-flash="status"]');
        if (status && session.flash?.status) {
            status.textContent = session.flash.status;
            status.hidden = false;
        }
        current = session;
        fillHiddenPrices(document);
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

    /**
     * Cửa hàng ẩn giá với khách chưa đăng nhập: HTML cache luôn là bản "Đăng nhập để xem giá" → điền giá cho khách đã đăng
     * nhập trong `root`. Gọi lại cho vùng DOM thay sau (lọc danh sách, giỏ mini, plugin) qua `vani:content-updated`.
     */
    function fillHiddenPrices(root) {
        const session = current;
        if (!session?.signed_in || !session.prices_url || !(root instanceof Element || root instanceof Document)) return;
        const hiddenPrices = [...root.querySelectorAll('[data-vani-price-for]:not([data-vani-price-loading])')];
        if (!hiddenPrices.length) return;
        hiddenPrices.forEach((el) => el.setAttribute('data-vani-price-loading', ''));
        const query = new URLSearchParams([...new Set(hiddenPrices.map((el) => el.dataset.vaniPriceFor))].map((id) => ['product_ids[]', id]));
        // Storefront API bằng phiên cùng nguồn (ADR-044): trình duyệt gửi Sec-Fetch-Site: same-origin.
        fetch(`${session.prices_url}?${query}`, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
            .then((response) => (response.ok ? response.json() : null))
            .then((json) => {
                const data = json?.data;
                hiddenPrices.forEach((el) => {
                    const price = data?.products?.[el.dataset.vaniPriceFor];
                    el.removeAttribute('data-vani-price-loading');
                    if (!price) return;
                    const span = document.createElement('span');
                    span.className = 'font-semibold text-primary';
                    span.textContent = price.max.amount !== price.min.amount ? `${price.min.formatted} – ${price.max.formatted}` : price.min.formatted;
                    el.replaceWith(span);
                });
            })
            .catch(() => hiddenPrices.forEach((el) => el.removeAttribute('data-vani-price-loading')));
    }

    function mount({ updateCartCount, dispatchCartChange }) {
        document.addEventListener('vani:content-updated', (event) => fillHiddenPrices(event.detail?.element));
        const apply = (session) => applySession(session, updateCartCount, dispatchCartChange);
        applyLoaded = apply;
        // Back/forward cache: trang hiện lại nguyên trạng cũ (số món, tài khoản) → lấy lại phiên; đăng nhập/đăng xuất ở nơi
        // khác thì tải lại trang (giá, nội dung theo khách đã điền không còn đúng).
        window.addEventListener('pageshow', (event) => {
            if (!event.persisted) return;
            const signedIn = current?.signed_in ?? null;
            loadSession().then((session) => {
                if (session && signedIn !== null && session.signed_in !== signedIn) window.location.reload();
            });
        });
        const inlineSession = document.querySelector('script[data-vani-session-state]');
        if (inlineSession) {
            try {
                apply(JSON.parse(inlineSession.textContent || 'null'));
            } catch {
                // dữ liệu hỏng: bỏ qua
            }
        }

        if (document.documentElement.dataset.vaniSession) loadSession();
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
