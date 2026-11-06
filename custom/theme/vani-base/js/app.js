/*
 * Đảo tương tác của vani-base (ADR-025): JS chỉ tăng cường, trang dùng được khi JS tắt.
 * Thư viện ngoài duy nhất: Swiper + PhotoSwipe đặt sẵn ở public/vendor, chỉ tải khi trang cần (loadVendor bên dưới).
 * Dữ liệu đọc từ data-* đã render sẵn ở server.
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

/**
 * Thư viện giao diện đặt sẵn ở public/vendor (không qua npm/Vite), tải theo nhu cầu: chỉ trang có carousel/ảnh phóng to
 * mới tải. Theme con và plugin dùng chung: `window.vaniLoadVendor('swiper' | 'photoswipe')` → Promise<class>.
 *   - Swiper 11 (MIT) — carousel. - PhotoSwipe 5 (MIT) — xem ảnh phóng to.
 * Gốc URL do layout in (`<html data-vani-vendor>`, theo ASSET_URL/CDN).
 */
const VENDORS = {
    swiper: { css: ['swiper/swiper-bundle.min.css'], js: ['swiper/swiper-bundle.min.js'], global: 'Swiper' },
    photoswipe: { css: ['photoswipe/photoswipe.css'], js: ['photoswipe/photoswipe.umd.min.js', 'photoswipe/photoswipe-lightbox.umd.min.js'], global: 'PhotoSwipeLightbox' },
};
const vendorPromises = {};
const vendorBase = (document.documentElement.dataset.vaniVendor || '/vendor').replace(/\/$/, '');
const loadScript = (src) =>
    new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = src;
        script.onload = resolve;
        script.onerror = () => reject(new Error(`Không tải được ${src}`));
        document.head.append(script);
    });
function loadVendor(name) {
    const vendor = VENDORS[name];
    if (!vendor) return Promise.reject(new Error(`Thư viện không có: ${name}`));
    vendorPromises[name] ??= (async () => {
        vendor.css.forEach((path) => {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = `${vendorBase}/${path}`;
            document.head.append(link);
        });
        // Theo thứ tự: bản lightbox của PhotoSwipe cần lõi đã có.
        for (const path of vendor.js) await loadScript(`${vendorBase}/${path}`);
        return window[vendor.global];
    })();
    return vendorPromises[name];
}
window.vaniLoadVendor = loadVendor;

/**
 * Carousel chung: phần tử `.vani-carousel.swiper > .swiper-wrapper > .swiper-slide`, tuỳ chọn Swiper ở `data-carousel`
 * (JSON), nút `[data-carousel-prev]`/`[data-carousel-next]`, chấm `[data-carousel-pagination]`. Trả Swiper instance.
 */
function mountCarousel(Swiper, element, extra = {}) {
    let options = {};
    try {
        options = JSON.parse(element.dataset.carousel || '{}');
    } catch {
        options = {};
    }
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    return new Swiper(element, {
        speed: reduceMotion ? 0 : 300,
        watchOverflow: true,
        a11y: { prevSlideMessage: t('carousel_prev'), nextSlideMessage: t('carousel_next') },
        navigation: { prevEl: element.querySelector('[data-carousel-prev]'), nextEl: element.querySelector('[data-carousel-next]') },
        pagination: element.querySelector('[data-carousel-pagination]') ? { el: element.querySelector('[data-carousel-pagination]'), clickable: true } : false,
        ...options,
        ...extra,
    });
}

const carousels = [...document.querySelectorAll('.vani-carousel:not([data-gallery-slider])')];
if (carousels.length) {
    loadVendor('swiper')
        .then((Swiper) => carousels.forEach((element) => mountCarousel(Swiper, element)))
        .catch(() => {}); // không tải được: vẫn là dải cuộn ngang
}

/**
 * Ảnh phóng to chung: vùng `[data-vani-lightbox]` chứa các `<a href=ảnh-lớn data-pswp-width data-pswp-height>`.
 * Không JS: liên kết mở ảnh lớn.
 */
function mountLightbox(PhotoSwipeLightbox, gallery, onChange) {
    const lightbox = new PhotoSwipeLightbox({
        gallery,
        children: 'a[data-pswp-width]',
        pswpModule: window.PhotoSwipe,
        bgOpacity: 0.95,
        showHideAnimationType: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'none' : 'zoom',
        closeTitle: t('lightbox_close'),
        zoomTitle: t('lightbox_zoom'),
        arrowPrevTitle: t('lightbox_prev'),
        arrowNextTitle: t('lightbox_next'),
        errorMsg: t('lightbox_error'),
    });
    if (onChange) lightbox.on('change', () => onChange(lightbox.pswp.currIndex));
    lightbox.init();
    return lightbox;
}

const lightboxes = [...document.querySelectorAll('[data-vani-lightbox]')];
if (lightboxes.length) {
    loadVendor('photoswipe')
        .then((PhotoSwipeLightbox) => lightboxes.forEach((gallery) => mountLightbox(PhotoSwipeLightbox, gallery)))
        .catch(() => {});
}

// PDP: gallery Swiper + phóng to PhotoSwipe; bấm ảnh nhỏ / chọn màu → tới ảnh đầu của màu (data-images trên fieldset màu).
document.querySelectorAll('[data-product-gallery]').forEach((gallery) => {
    const slider = gallery.querySelector('[data-gallery-slider]');
    const form = document.querySelector('[data-variant-form]');
    const thumbs = [...gallery.querySelectorAll('[data-gallery-thumb]')];
    if (!slider) {
        return;
    }
    let swiper = null;

    const highlight = (index) => {
        thumbs.forEach((thumb) => {
            const active = Number(thumb.dataset.index) === index;
            thumb.classList.toggle('border-primary', active);
            thumb.classList.toggle('border-slate-200', !active);
            thumb.setAttribute('aria-current', active ? 'true' : 'false');
            if (active) thumb.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        });
    };
    // Chưa có Swiper (đang tải/lỗi): cuộn dải ảnh scroll-snap tới ảnh.
    const go = (index) => {
        if (index < 0) return;
        if (swiper) {
            swiper.slideTo(index);
        } else {
            const slide = slider.querySelectorAll('[data-gallery-slide]')[index];
            slide?.parentElement.scrollTo({ left: slide.offsetLeft, behavior: 'smooth' });
            highlight(index);
        }
    };

    thumbs.forEach((thumb) => thumb.addEventListener('click', () => go(Number(thumb.dataset.index))));
    form?.addEventListener('change', (event) => {
        const group = event.target.closest('[data-color-group]');
        const images = group ? JSON.parse(group.dataset.images || '[]') : [];
        const first = images[0]?.url ?? images[0];
        if (first) go(thumbs.findIndex((thumb) => thumb.dataset.src === first));
    });

    if (slider.querySelectorAll('[data-gallery-slide]').length > 1) {
        loadVendor('swiper')
            .then((Swiper) => {
                swiper = mountCarousel(Swiper, slider, { on: { slideChange: (instance) => highlight(instance.activeIndex) } });
            })
            .catch(() => {});
    }
    loadVendor('photoswipe')
        .then((PhotoSwipeLightbox) => mountLightbox(PhotoSwipeLightbox, slider, (index) => go(index)))
        .catch(() => {});
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

// Trang sản phẩm: beacon lượt xem + "Đã xem gần đây" (§3.5-2). Danh sách chỉ ở trình duyệt (tối đa 30 mẫu — khối "Đã xem" của plugin đọc chung).
const recent = document.querySelector('[data-vani-recently-viewed]');
if (recent) {
    const KEY = 'vani.recently_viewed';
    const current = Number(recent.dataset.styleId);
    let ids = [];
    try {
        ids = (JSON.parse(localStorage.getItem(KEY) || '[]') || []).map(Number).filter((id) => id > 0);
    } catch {
        ids = [];
    }
    const others = ids.filter((id) => id !== current).slice(0, 6);
    try {
        localStorage.setItem(KEY, JSON.stringify([current, ...ids.filter((id) => id !== current)].slice(0, 30)));
    } catch {
        // trình duyệt chặn lưu trữ: bỏ qua
    }
    const body = new FormData();
    body.append('style_id', String(current));
    if (!(navigator.sendBeacon && navigator.sendBeacon(recent.dataset.viewUrl, body))) {
        fetch(recent.dataset.viewUrl, { method: 'POST', body, credentials: 'same-origin', keepalive: true }).catch(() => {});
    }
    if (others.length) {
        fetch(`${recent.dataset.productsUrl}?ids=${others.join(',')}`, { headers: { Accept: 'application/json', 'X-Vani-Locale': recent.dataset.locale || '' } })
            .then((response) => (response.ok ? response.json() : null))
            .then((json) => {
                const cards = (json?.data || []).map((product) => {
                    const link = document.createElement('a');
                    link.href = recent.dataset.productUrl.replace('__slug__', encodeURIComponent(product.slug));
                    link.className = 'block text-sm';
                    if (product.image_url) {
                        const image = document.createElement('img');
                        image.src = product.image_url;
                        image.alt = '';
                        image.loading = 'lazy';
                        image.className = 'mb-2 aspect-[3/4] w-full rounded-[var(--radius-theme)] object-cover';
                        link.append(image);
                    }
                    const name = document.createElement('span');
                    name.className = 'block font-medium';
                    name.textContent = product.name || '';
                    link.append(name);
                    if (product.price?.min?.formatted) {
                        const price = document.createElement('span');
                        price.className = 'block text-primary';
                        price.textContent = product.price.min.formatted;
                        link.append(price);
                    }
                    return link;
                });
                if (!cards.length) return;
                recent.querySelector('[data-vani-recently-viewed-list]').replaceChildren(...cards);
                recent.hidden = false;
            })
            .catch(() => {});
    }
}

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
