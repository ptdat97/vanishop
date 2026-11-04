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

const sessionUrl = document.documentElement.dataset.vaniSession;
if (sessionUrl) {
    fetch(sessionUrl, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
        .then((response) => (response.ok ? response.json() : null))
        .then((session) => {
            if (!session) return;
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
            const status = document.querySelector('[data-vani-flash="status"]');
            if (status && session.flash.status) {
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
            Object.entries(session.flash.errors || {}).forEach(([field, message]) => {
                const target = document.querySelector(`[data-vani-error-for="${field}"]`) || document.querySelector('[data-vani-error-for="business"]');
                if (target && message) {
                    target.textContent = message;
                    target.hidden = false;
                }
            });
        })
        .catch(() => {});
}

