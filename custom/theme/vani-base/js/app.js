/*
 * Đảo tương tác của vani-base (ADR-025): JS chỉ tăng cường, trang dùng được khi JS tắt.
 * Không phụ thuộc thư viện ngoài; dữ liệu đọc từ data-* đã render sẵn ở server.
 */
document.documentElement.classList.add('js');

// Chuỗi giao diện đã dịch theo ngôn ngữ trang (layout xuất JSON `data-vani-i18n`, khoá = id ổn định).
const i18n = (() => {
    try {
        return JSON.parse(document.querySelector('script[data-vani-i18n]')?.textContent ?? '{}');
    } catch {
        return {};
    }
})();
const t = (key, replace = {}) => Object.entries(replace).reduce((text, [name, value]) => text.replace(`:${name}`, value), i18n[key] ?? key);

// PDP: chọn màu → đổi ảnh chính theo bộ ảnh của màu (data-images trên fieldset màu).
document.querySelectorAll('[data-product-gallery]').forEach((gallery) => {
    const main = gallery.querySelector('[data-gallery-main]');
    const form = document.querySelector('[data-variant-form]');
    if (!main) {
        return;
    }

    // Ảnh là {url, alt} (cùng hình dạng Storefront API).
    const show = (url) => {
        main.src = url;
        gallery.querySelectorAll('[data-gallery-thumb]').forEach((thumb) => {
            thumb.classList.toggle('border-primary', thumb.dataset.src === url);
            thumb.classList.toggle('border-slate-200', thumb.dataset.src !== url);
        });
    };

    gallery.querySelectorAll('[data-gallery-thumb]').forEach((thumb) => thumb.addEventListener('click', () => show(thumb.dataset.src)));

    form?.addEventListener('change', (event) => {
        const group = event.target.closest('[data-color-group]');
        const images = group ? JSON.parse(group.dataset.images || '[]') : [];
        if (images.length > 0) {
            show(images[0].url ?? images[0]);
        }
    });
});

// Giỏ: đổi số lượng thì gửi form luôn (nút "Cập nhật" vẫn có cho trường hợp JS tắt).
document.querySelectorAll('[data-autosubmit]').forEach((input) => {
    input.addEventListener('change', () => input.form?.requestSubmit());
});

// Giỏ: chọn dòng để thanh toán — đổi ô chọn thì gửi form lựa chọn; "Chọn tất cả" đánh dấu mọi dòng chọn được.
const selection = document.getElementById('cart-selection');
if (selection) {
    selection.querySelector('[data-selection-submit]')?.setAttribute('hidden', '');
    const lines = document.querySelectorAll('[data-select-line]');
    lines.forEach((input) => input.addEventListener('change', () => selection.requestSubmit()));
    selection.querySelector('[data-select-all]')?.addEventListener('change', (event) => {
        lines.forEach((input) => {
            if (!input.disabled) input.checked = event.target.checked;
        });
        selection.requestSubmit();
    });
}

// Checkout: chọn tỉnh → tải phường/xã từ Storefront API (không JS: nút "Tải danh sách phường/xã").
document.querySelectorAll('[data-province-select]').forEach((province) => {
    const ward = province.form?.querySelector('[data-ward-select]');
    if (!ward) {
        return;
    }

    province.addEventListener('change', async () => {
        ward.replaceChildren(new Option(t('wards_loading'), ''));
        if (!province.value) {
            ward.replaceChildren(new Option(t('province_first'), ''));
            return;
        }
        try {
            const response = await fetch(`${province.dataset.wardsUrl}/${encodeURIComponent(province.value)}/wards`, {
                headers: { Accept: 'application/json' },
            });
            const { data } = await response.json();
            ward.replaceChildren(new Option(t('choose_ward'), ''), ...data.map((item) => new Option(item.name, item.code)));
        } catch {
            ward.replaceChildren(new Option(t('wards_failed'), ''));
        }
    });
});

// Form cần xác nhận (xoá địa chỉ…): không JS thì gửi luôn.
document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
});

// Trang cache được (không phiên, storefront §5): lấy phần riêng của khách — đăng nhập, số món trong giỏ, thông báo/lỗi
// của lần gửi trước. Không có JS: trang vẫn mua được, chỉ không hiện các chi tiết này.
/**
 * Sự kiện trình duyệt khi giỏ đổi (0.3.43) — plugin (đo quảng cáo…) nghe `document.addEventListener('vani:cart-updated', …)`.
 * detail: { action: 'add' | 'remove', variant_id, sku, name, quantity, unit_amount, currency }.
 */
function dispatchCartChange(change) {
    if (change) {
        document.dispatchEvent(new CustomEvent('vani:cart-updated', { detail: change }));
    }
}
const inlineCartChange = document.querySelector('script[data-vani-cart-change]');
if (inlineCartChange) {
    try {
        dispatchCartChange(JSON.parse(inlineCartChange.textContent || 'null'));
    } catch {
        // dữ liệu hỏng: bỏ qua
    }
}

/** Giỏ mini ở header: vài dòng gần nhất + tạm tính (không khuyến mãi). Dựng bằng DOM, không chèn HTML từ dữ liệu. */
function renderMiniCart(cart) {
    const box = document.querySelector('[data-vani-mini-cart]');
    const list = box?.querySelector('[data-vani-mini-cart-lines]');
    if (!box || !list || !cart || !(cart.lines || []).length) return;

    list.replaceChildren(
        ...cart.lines.map((line) => {
            const item = document.createElement('li');
            item.className = 'flex items-center gap-2 py-2';
            if (line.image_url) {
                const image = document.createElement('img');
                image.src = line.image_url;
                image.alt = '';
                image.className = 'h-10 w-10 rounded object-cover';
                item.append(image);
            }
            const text = document.createElement('span');
            text.className = 'flex-1';
            text.textContent = `${line.name ?? ''}${line.variant ? ` (${line.variant})` : ''} × ${line.quantity}`;
            const total = document.createElement('span');
            total.textContent = line.line_total?.formatted ?? '';
            item.append(text, total);

            return item;
        }),
    );
    box.querySelector('[data-vani-mini-cart-subtotal]').textContent = cart.subtotal?.formatted ?? '';
    box.hidden = false;
}

// Ô tìm kiếm: gợi ý sản phẩm, danh mục, thương hiệu khi gõ ≥ 2 ký tự (Storefront API). Dựng bằng DOM, không chèn HTML.
document.querySelectorAll('form[data-vani-suggest]').forEach((form) => {
    const input = form.querySelector('input[name="q"]');
    const box = form.querySelector('[data-vani-suggest-box]');
    if (!input || !box) return;
    let timer;
    let controller;
    const group = (title, items, render) => {
        if (!items.length) return [];
        const heading = document.createElement('p');
        heading.className = 'px-2 pt-2 text-xs font-medium uppercase text-slate-500';
        heading.textContent = title;
        return [heading, ...items.map(render)];
    };
    const link = (item, label) => {
        const anchor = document.createElement('a');
        anchor.href = item.url;
        anchor.className = 'flex items-center gap-2 rounded px-2 py-1.5 hover:bg-slate-100';
        if (item.image_url) {
            const image = document.createElement('img');
            image.src = item.image_url;
            image.alt = '';
            image.className = 'h-8 w-8 rounded object-cover';
            anchor.append(image);
        }
        anchor.append(document.createTextNode(label ?? item.name ?? ''));
        return anchor;
    };
    const close = () => {
        box.hidden = true;
        box.replaceChildren();
    };
    input.addEventListener('input', () => {
        clearTimeout(timer);
        const query = input.value.trim();
        if (query.length < 2) return close();
        timer = setTimeout(() => {
            controller?.abort();
            controller = new AbortController();
            fetch(`${form.dataset.vaniSuggest}?${new URLSearchParams({ q: query })}`, { headers: { Accept: 'application/json', 'X-Vani-Locale': form.dataset.locale || '' }, signal: controller.signal })
                .then((response) => (response.ok ? response.json() : null))
                .then((json) => {
                    const data = json?.data;
                    if (!data) return close();
                    const nodes = [
                        ...group(t('suggest_products'), data.products, (item) => link(item)),
                        ...group(t('suggest_categories'), data.categories, (item) => link(item)),
                        ...group(t('suggest_brands'), data.brands, (item) => link(item)),
                    ];
                    if (!nodes.length) return close();
                    const all = link({ url: data.search_url }, t('suggest_all'));
                    all.classList.add('mt-1', 'border-t', 'border-slate-100', 'font-medium');
                    box.replaceChildren(...nodes, all);
                    box.hidden = false;
                })
                .catch(() => {});
        }, 200);
    });
    input.addEventListener('keydown', (event) => event.key === 'Escape' && close());
    document.addEventListener('click', (event) => !form.contains(event.target) && close());
});

/**
 * Phần riêng của khách (storefront §5): không CDN thì layout nhúng sẵn `script[data-vani-session-state]`; trang cache CDN
 * lấy từ /_vani/phien (thêm flash + cart_change). Cùng một hàm áp dụng → hai chế độ hiển thị như nhau.
 */
function applySession(session) {
    if (!session) return;
    // Cho plugin (0.3.76): đọc `window.vaniSession` hoặc nghe `vani:session` — signed_in, csrf_token, cart…
    window.vaniSession = session;
    document.dispatchEvent(new CustomEvent('vani:session', { detail: session }));
    dispatchCartChange(session.cart_change);
    const account = document.querySelector('[data-vani-account]');
    if (account) {
        account.textContent = session.account.label;
        account.setAttribute('href', session.account.url);
    }
    const count = document.querySelector('[data-vani-cart-count]');
    if (count && session.cart.count > 0) {
        count.textContent = String(session.cart.count);
        count.hidden = false;
    }
    renderMiniCart(session.cart);
    const status = document.querySelector('[data-vani-flash="status"]');
    if (status && session.flash?.status) {
        status.textContent = session.flash.status;
        status.hidden = false;
    }
    // Cửa hàng ẩn giá với khách chưa đăng nhập: HTML cache luôn là bản "Đăng nhập để xem giá" → điền giá cho khách đã đăng nhập.
    const hiddenPrices = [...document.querySelectorAll('[data-vani-price-for]')];
    if (session.signed_in && hiddenPrices.length && session.prices_url) {
        const query = new URLSearchParams([...new Set(hiddenPrices.map((el) => el.dataset.vaniPriceFor))].map((id) => ['p[]', id]));
        fetch(`${session.prices_url}?${query}`, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
            .then((response) => (response.ok ? response.json() : null))
            .then((data) => {
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
    const variantIds = [...document.querySelectorAll('input[name="variant_id"]')].map((input) => input.value);
    if (session.signed_in && memberPrice && variantIds.length) {
        const query = new URLSearchParams(variantIds.map((id) => ['v[]', id]));
        fetch(`${memberPrice.dataset.route}?${query}`, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
            .then((response) => (response.ok ? response.json() : null))
            .then((data) => {
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

const inlineSession = document.querySelector('script[data-vani-session-state]');
if (inlineSession) {
    try {
        applySession(JSON.parse(inlineSession.textContent || 'null'));
    } catch {
        // dữ liệu hỏng: bỏ qua
    }
}

const sessionUrl = document.documentElement.dataset.vaniSession;
if (sessionUrl) {
    fetch(sessionUrl, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
        .then((response) => (response.ok ? response.json() : null))
        .then(applySession)
        .catch(() => {});
}
