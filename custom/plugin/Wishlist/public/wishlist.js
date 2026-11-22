// vani.wishlist — tô nút tim theo danh sách của khách và thêm/bỏ không tải lại trang (đặc tả §2).
// Khách vãng lai: danh sách trong localStorage; đăng nhập: server, lần đầu gộp danh sách của trình duyệt rồi xoá bản cục bộ.
(function () {
    const script = document.querySelector('script[data-vani-wishlist-script]');
    if (!script) return;
    const KEY = 'vani.wishlist';
    const MAX = 100;
    const config = script.dataset;
    let ids = new Set();
    let session = null;

    const readLocal = () => {
        try {
            const parsed = JSON.parse(localStorage.getItem(KEY) || '[]');
            return Array.isArray(parsed) ? parsed.map(Number).filter((id) => id > 0).slice(0, MAX) : [];
        } catch {
            return [];
        }
    };
    const writeLocal = (list) => {
        try {
            if (list.length) localStorage.setItem(KEY, JSON.stringify(list.slice(0, MAX)));
            else localStorage.removeItem(KEY);
        } catch {
            // trình duyệt chặn lưu trữ: bỏ qua
        }
    };
    // Storefront API bằng phiên cùng nguồn (ADR-044): cookie phiên + X-CSRF-TOKEN của trang, phản hồi `{data: {...}}`.
    const call = (method, url, body = null) =>
        fetch(url, {
            method,
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': session?.csrf_token || '' },
            body: body === null ? null : JSON.stringify(body),
        }).then((response) => (response.ok ? response.json() : null)).then((json) => json?.data ?? null);

    const render = () => {
        document.querySelectorAll('[data-vani-wishlist]').forEach((wrapper) => {
            const active = ids.has(Number(wrapper.dataset.vaniWishlist));
            const button = wrapper.querySelector('[data-vani-wishlist-button]');
            const label = wrapper.querySelector('[data-vani-wishlist-label]');
            button?.setAttribute('aria-pressed', active ? 'true' : 'false');
            if (label) label.textContent = active ? config.labelRemove : config.labelAdd;
        });
        const count = document.querySelector('[data-vani-wishlist-count]');
        if (count) {
            count.textContent = ids.size ? String(ids.size) : '';
            count.hidden = ids.size === 0;
        }
    };

    const guest = document.querySelector('[data-wishlist-guest]');
    let pendingPreview;
    const loadGuest = async () => {
        if (!guest) return;
        pendingPreview?.abort();
        const items = guest.querySelector('[data-guest-items]');
        const retry = guest.querySelector('[data-guest-retry]');
        retry.hidden = true;
        guest.querySelector('[data-guest-empty]').hidden = ids.size > 0;
        if (!ids.size) {
            items.replaceChildren();
            return;
        }
        const request = new AbortController();
        pendingPreview = request;
        try {
            const response = await fetch(guest.dataset.wishlistGuest, {
                method: 'POST', credentials: 'same-origin', signal: request.signal,
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': session.csrf_token },
                body: JSON.stringify({ style_ids: [...ids].reverse() }),
            });
            if (!response.ok) throw new Error(String(response.status));
            const html = await response.text();
            if (request.signal.aborted) return;
            items.innerHTML = html;
            document.dispatchEvent(new CustomEvent('vani:content-updated', { detail: { element: items } }));
        } catch {
            if (!request.signal.aborted) retry.hidden = false;
        }
    };
    guest?.querySelector('[data-guest-retry]').addEventListener('click', loadGuest);

    const start = (detail) => {
        if (session) return;
        session = detail;
        if (!session.signed_in) {
            ids = new Set(readLocal());
            render();
            loadGuest();
            return;
        }
        const local = readLocal();
        const load = local.length
            ? call('POST', config.mergeUrl, { style_ids: local }).then((data) => {
                  if (data) writeLocal([]);
                  return data;
              })
            : call('GET', config.idsUrl);
        load.then((data) => {
            ids = new Set((data?.ids || []).map(Number));
            render();
        }).catch(() => {});
    };

    // Nút tim nằm trong thẻ <a> của thẻ sản phẩm: chặn điều hướng của link cha.
    document.addEventListener('click', (event) => {
        const button = event.target.closest?.('[data-vani-wishlist] [data-vani-wishlist-button]');
        if (!button) return;
        event.preventDefault();
        event.stopPropagation();
        if (!session) return;
        const styleId = Number(button.closest('[data-vani-wishlist]').dataset.vaniWishlist);
        if (!session.signed_in) {
            ids.has(styleId) ? ids.delete(styleId) : ids.size < MAX && ids.add(styleId);
            writeLocal([...ids]);
            render();
            loadGuest();
            return;
        }
        (ids.has(styleId) ? call('DELETE', `${config.itemsUrl}/${styleId}`) : call('POST', config.itemsUrl, { style_id: styleId }))
            .then((data) => {
                if (!data) return;
                ids = new Set((data.ids || []).map(Number));
                render();
            })
            .catch(() => {});
    });

    // Trang yêu thích trong tài khoản: bỏ một mẫu rồi gỡ thẻ khỏi lưới (hết mẫu: tải lại để hiện trạng thái trống).
    document.addEventListener('click', (event) => {
        const button = event.target.closest?.('[data-vani-wishlist-remove]');
        if (!button || !session) return;
        if (!session.signed_in && guest) {
            ids.delete(Number(button.dataset.vaniWishlistRemove));
            writeLocal([...ids]);
            render();
            loadGuest();
            return;
        }
        if (!session.signed_in) return;
        event.preventDefault();
        button.disabled = true;
        call('DELETE', `${config.itemsUrl}/${button.dataset.vaniWishlistRemove}`)
            .then((data) => {
                if (!data) {
                    button.disabled = false;
                    return;
                }
                ids = new Set((data.ids || []).map(Number));
                button.closest('[data-vani-wishlist-item]')?.remove();
                if (!document.querySelector('[data-vani-wishlist-item]')) window.location.reload();
                render();
            })
            .catch(() => {
                button.disabled = false;
            });
    });

    // Theme thay HTML không tải lại trang (lọc/phân trang danh sách, giỏ mini — vani-base 0.3.93): tô lại nút tim mới.
    document.addEventListener('vani:content-updated', () => session && render());
    if (window.vaniSession) start(window.vaniSession);
    document.addEventListener('vani:session', (event) => start(event.detail));
})();
