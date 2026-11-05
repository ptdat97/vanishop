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
    const post = (url, body) =>
        fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': session?.csrf_token || '' },
            body: JSON.stringify(body),
        }).then((response) => (response.ok ? response.json() : null));

    const render = () => {
        document.querySelectorAll('form[data-vani-wishlist]').forEach((form) => {
            const active = ids.has(Number(form.dataset.vaniWishlist));
            const button = form.querySelector('[data-vani-wishlist-button]');
            const label = form.querySelector('[data-vani-wishlist-label]');
            button?.setAttribute('aria-pressed', active ? 'true' : 'false');
            if (label) label.textContent = active ? config.labelRemove : config.labelAdd;
        });
        const count = document.querySelector('[data-vani-wishlist-count]');
        if (count) {
            count.textContent = ids.size ? String(ids.size) : '';
            count.hidden = ids.size === 0;
        }
    };

    const start = (detail) => {
        if (session) return;
        session = detail;
        if (!session.signed_in) {
            ids = new Set(readLocal());
            render();
            return;
        }
        const local = readLocal();
        const load = local.length
            ? post(config.mergeUrl, { style_ids: local }).then((data) => {
                  if (data) writeLocal([]);
                  return data;
              })
            : fetch(config.idsUrl, { credentials: 'same-origin', headers: { Accept: 'application/json' } }).then((response) => (response.ok ? response.json() : null));
        load.then((data) => {
            ids = new Set((data?.ids || []).map(Number));
            render();
        }).catch(() => {});
    };

    document.addEventListener('submit', (event) => {
        const form = event.target.closest?.('form[data-vani-wishlist]');
        if (!form || !session) return;
        event.preventDefault();
        const styleId = Number(form.dataset.vaniWishlist);
        if (!session.signed_in) {
            ids.has(styleId) ? ids.delete(styleId) : ids.size < MAX && ids.add(styleId);
            writeLocal([...ids]);
            render();
            return;
        }
        post(config.toggleUrl, { style_id: styleId })
            .then((data) => {
                if (!data) return;
                data.in_wishlist ? ids.add(styleId) : ids.delete(styleId);
                render();
            })
            .catch(() => {});
    });

    if (window.vaniSession) start(window.vaniSession);
    document.addEventListener('vani:session', (event) => start(event.detail));
})();
