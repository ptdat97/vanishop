import { createCart, dispatchCartChange, dispatchInlineCartChange, updateCartCount } from './cart.js';
import { createIcons, Heart, House, LayoutGrid, Menu, Search, ShoppingBag, Sparkles, UserRound } from 'lucide';
import { listing, mountListingNavigation } from './listing.js';
import { mountAccountMenu, mountDialogs, mountSearchSuggestions } from './header.js';
import { createSession } from './session.js';
import { mountProduct } from './product.js';
import { mountCheckout } from './checkout.js';

mountAccountMenu();

/*
 * Đảo tương tác của vani-base (ADR-025): HTML render sẵn ở server (SEO, hiển thị nhanh), JS gắn tương tác.
 * Thư viện giao diện (Motion, Swiper, PhotoSwipe) chỉ tải khi trang cần (loadVendor bên dưới).
 * Dữ liệu đọc từ data-* đã render sẵn ở server.
 */
createIcons({ icons: { Heart, House, LayoutGrid, Menu, Search, ShoppingBag, Sparkles, UserRound } });
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

/*
 * ── window.vani (0.3.93): API cho frontend nạp dữ liệu không tải lại trang ─────────────────────────────────────────────
 * Gọi chính các route web của storefront với `Accept: application/json` (cùng phiên, validation, throttle như form). URL theo ngôn ngữ trang lấy từ `script[data-vani-urls]`. Lỗi: Error có `status`, `errors` (theo trường),
 * `code`. Theme con / plugin dùng lại:
 *   vani.cart.get() · add(variantId, quantity, options) · addItems([{variant_id, quantity}]) · update(lineId, quantity) · remove(lineId) · select(lineIds)
 *   vani.cart.applyVoucher(code) · removeVoucher() · open(state?) · close()
 *   vani.auth.login(email, password) · logout()
 *   vani.search.suggest(q) · listing(url)        vani.request(method, url, data)
 * Sự kiện: `vani:cart` (detail = trạng thái giỏ), `vani:cart-updated` (thay đổi, 0.3.43), `vani:content-updated` (đã thay HTML).
 */
const urls = (() => {
    try {
        return JSON.parse(document.querySelector('script[data-vani-urls]')?.textContent ?? '{}');
    } catch {
        return {};
    }
})();
// Trang giỏ đầy đủ (0.3.94): JSON trả thêm `page_html` để thay vùng này.
const cartPage = document.querySelector('[data-vani-cart-page]');
const session = createSession({ urls, cartPage, t });
const { request, auth } = session;

const firstError = (error, field) => (error.errors?.[field] || [])[0] || error.message;

mountDialogs();

const { cart, mountCartPage, mountMiniCart, mountProductCart } = createCart({ urls, request, cartPage, firstError });

const search = {
    suggest: (query) =>
        fetch(`${urls.search_suggestions}?${new URLSearchParams({ q: query })}`, { headers: { Accept: 'application/json', 'X-Vani-Locale': document.documentElement.lang || '' } })
            .then((response) => (response.ok ? response.json() : { data: null }))
            .then((json) => json.data),
    listing,
};

window.vani = { request, cart, auth, search, urls };

/**
 * Thư viện giao diện cài qua npm, tách chunk riêng bằng import() động để chỉ tải khi cần. Theme con và plugin dùng chung:
 * `window.vaniLoadVendor('swiper' | 'photoswipe' | 'motion')` → Promise thư viện tương ứng.
 * Motion cung cấp `animate`, `inView`, `scroll` cho animation DOM; ví dụ: `const { animate } = await vaniLoadVendor('motion')`.
 */
const VENDORS = {
    swiper: () => Promise.all([import('swiper/bundle'), import('swiper/css/bundle')]).then(([module]) => module.default),
    photoswipe: () => Promise.all([import('photoswipe/lightbox'), import('photoswipe/style.css')]).then(([module]) => module.default),
    motion: () => import('motion'),
};
const vendorPromises = {};
function loadVendor(name) {
    if (!VENDORS[name]) return Promise.reject(new Error(`Thư viện không có: ${name}`));
    vendorPromises[name] ??= VENDORS[name]();
    return vendorPromises[name];
}
window.vaniLoadVendor = loadVendor;

/*
 * Hiệu ứng hiện dần khi cuộn tới: `[data-vani-motion="rise|fade"]`, nhóm so le `[data-vani-motion-stagger]` >
 * `[data-vani-motion-item]`. Chỉ CSS transition + IntersectionObserver (không tải thư viện Motion — ~60 KB gzip — cho
 * mọi trang danh sách). Phần tử đã nằm trong màn hình đầu lúc tải trang hiện ngay, không chờ hiệu ứng (ảnh LCP không bị
 * ẩn). Nội dung chèn sau (`vani:content-updated`) vẫn có hiệu ứng.
 */
const motionElementSelector = '[data-vani-motion]';
const motionStaggerSelector = '[data-vani-motion-stagger]';
const motionItemSelector = '[data-vani-motion-item]';
if (
    document.querySelector(`${motionElementSelector}, ${motionStaggerSelector}`) &&
    !window.matchMedia('(prefers-reduced-motion: reduce)').matches &&
    'IntersectionObserver' in window
) {
    const observed = new WeakSet();
    const staggered = new WeakSet();
    // Mỗi phần tử theo dõi riêng; các mục so le hiện cùng một lượt (cùng hàng) lệch nhau 45 ms.
    const observer = new IntersectionObserver((entries) => {
        const visible = entries.filter((entry) => entry.isIntersecting).map((entry) => entry.target);
        visible.forEach((element) => observer.unobserve(element));
        requestAnimationFrame(() => {
            let order = 0;
            visible.forEach((element) => {
                const delay = staggered.has(element) ? Math.min(order++, 6) * 45 : 0;
                element.style.setProperty('--vani-motion-delay', `${delay}ms`);
                element.removeAttribute('data-vani-motion-pending');
            });
        });
    });
    const inFirstScreen = (element) => {
        const rect = element.getBoundingClientRect();
        return rect.top < window.innerHeight && rect.bottom > 0;
    };
    const watch = (element, initial) => {
        if (observed.has(element)) return;
        observed.add(element);
        if (initial && inFirstScreen(element)) return;
        element.setAttribute('data-vani-motion-pending', '');
        observer.observe(element);
    };

    const observeMotionTargets = (root, initial) => {
        const findTargets = (selector) => {
            const targets = [...root.querySelectorAll(selector)];
            if (root instanceof Element && root.matches(selector)) {
                targets.unshift(root);
            }
            return targets;
        };

        findTargets(motionStaggerSelector).forEach((group) => {
            [...group.querySelectorAll(motionItemSelector)]
                .filter((item) => item.closest(motionStaggerSelector) === group)
                .forEach((item) => {
                    staggered.add(item);
                    watch(item, initial);
                });
        });

        findTargets(motionElementSelector).forEach((target) => {
            if (staggered.has(target) || target.parentElement?.closest(motionElementSelector)) return;
            watch(target, initial);
        });
    };

    document.documentElement.classList.add('vani-motion-ready');
    observeMotionTargets(document, true);
    document.addEventListener('vani:content-updated', (event) => {
        if (event.detail?.element instanceof Element) {
            observeMotionTargets(event.detail.element, false);
        }
    });
}

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
    if (reduceMotion) options.autoplay = false;
    const swiper = new Swiper(element, {
        speed: reduceMotion ? 0 : 300,
        watchOverflow: true,
        a11y: { prevSlideMessage: t('carousel_prev'), nextSlideMessage: t('carousel_next') },
        navigation: { prevEl: element.querySelector('[data-carousel-prev]'), nextEl: element.querySelector('[data-carousel-next]') },
        pagination: element.querySelector('[data-carousel-pagination]') ? { el: element.querySelector('[data-carousel-pagination]'), clickable: true } : false,
        ...options,
        ...extra,
    });
    if (swiper.autoplay?.running) {
        const stop = () => swiper.autoplay.stop();
        const restart = () => {
            if (!element.matches(':hover, :focus-within') && !swiper.autoplay.running) swiper.autoplay.start();
        };
        element.addEventListener('mouseenter', stop);
        element.addEventListener('mouseleave', restart);
        element.addEventListener('focusin', stop);
        element.addEventListener('focusout', () => queueMicrotask(restart));
        if (element.matches(':hover, :focus-within')) stop();
    }
    return swiper;
}

const carousels = [...document.querySelectorAll('.vani-carousel:not([data-gallery-slider])')];
if (carousels.length) {
    loadVendor('swiper')
        .then((Swiper) => carousels.forEach((element) => mountCarousel(Swiper, element)))
        .catch(() => {}); // không tải được: vẫn là dải cuộn ngang
}

/**
 * Ảnh phóng to chung: vùng `[data-vani-lightbox]` chứa các `<a href=ảnh-lớn data-pswp-width data-pswp-height>`.
 * Trước khi thư viện tải xong: liên kết mở ảnh lớn.
 */
function mountLightbox(PhotoSwipeLightbox, gallery, onChange, PhotoSwipe = null) {
    const lightbox = new PhotoSwipeLightbox({
        gallery,
        children: 'a[data-pswp-width]',
        pswpModule: PhotoSwipe ?? (() => import('photoswipe')),
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

mountProduct({ loadVendor, mountLightbox });

mountCartPage();

/** Chữ thường, bỏ dấu tiếng Việt (đ → d) — so khớp "phuong ben thanh" với "Phường Bến Thành". */
const LETTER_D_STROKE = new RegExp(String.fromCharCode(0x111), 'gi'); // đ/Đ
const foldVietnamese = (text) => text.normalize('NFD').replace(/[̀-ͯ]/g, '').replace(LETTER_D_STROKE, 'd').toLowerCase().trim();

/** Ô tìm phường/xã (`[data-ward-filter]`) lọc danh sách đang có của select; hiện khi danh sách dài. */
function mountWardFilter(filter, ward) {
    let all = [];
    const remember = () => {
        all = [...ward.options].filter((option) => option.value !== '').map((option) => ({ value: option.value, label: option.textContent, key: foldVietnamese(option.textContent) }));
        filter.value = '';
        filter.hidden = all.length < 15;
    };
    filter.addEventListener('input', () => {
        const query = foldVietnamese(filter.value);
        const selected = ward.value;
        const matches = all.filter((item) => item.key.includes(query));
        const current = all.find((item) => item.value === selected);
        const options = current && !matches.includes(current) && matches.length !== 1 ? [current, ...matches] : matches;
        ward.replaceChildren(
            new Option(matches.length ? t('choose_ward') : t('wards_no_match'), ''),
            ...options.map((item) => new Option(item.label, item.value, false, item.value === selected)),
        );
        if (matches.length === 1 && matches[0].value !== selected) {
            ward.value = matches[0].value;
            ward.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });
    // Enter trong ô tìm không gửi form.
    filter.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            ward.focus();
        }
    });
    remember();
    return remember;
}

// Checkout / sổ địa chỉ: chọn tỉnh → tải phường/xã từ Storefront API.
document.querySelectorAll('[data-province-select]').forEach((province) => {
    const ward = province.form?.querySelector('[data-ward-select]');
    if (!ward) {
        return;
    }
    const filter = province.form.querySelector('[data-ward-filter]');
    const retry = province.form.querySelector('[data-ward-retry]');
    const rememberWards = filter ? mountWardFilter(filter, ward) : () => {};
    let wardRequest;

    const setLoading = (loading) => {
        ward.disabled = loading;
        if (loading) ward.setAttribute('aria-busy', 'true');
        else ward.removeAttribute('aria-busy');
        if (retry) retry.disabled = loading;
        ward.dispatchEvent(new Event('vani:wards-state'));
    };
    province.vaniLoadWards = async (selected = '') => {
        wardRequest?.abort();
        const request = new AbortController();
        wardRequest = request;
        if (!province.value) {
            if (retry) retry.hidden = true;
            ward.replaceChildren(new Option(t('province_first'), ''));
            rememberWards();
            setLoading(false);
            return;
        }
        ward.replaceChildren(new Option(t('wards_loading'), ''));
        if (filter) filter.hidden = true;
        setLoading(true);
        try {
            const response = await fetch(`${province.dataset.wardsUrl}/${encodeURIComponent(province.value)}/wards`, {
                headers: { Accept: 'application/json' },
                signal: request.signal,
            });
            if (!response.ok) throw new Error(String(response.status));
            const { data } = await response.json();
            if (request.signal.aborted) return;
            ward.replaceChildren(new Option(t('choose_ward'), ''), ...data.map((item) => new Option(item.name, item.code, false, item.code === selected)));
            if (retry) retry.hidden = true;
        } catch {
            if (request.signal.aborted) return;
            ward.replaceChildren(new Option(t('wards_failed'), ''));
            if (retry) retry.hidden = false;
        } finally {
            if (!request.signal.aborted) {
                rememberWards();
                setLoading(false);
            }
        }
    };
    province.addEventListener('change', () => province.vaniLoadWards());
    retry?.addEventListener('click', async () => {
        await province.vaniLoadWards();
        if (retry.hidden && !ward.disabled) ward.focus();
    });
});

mountCheckout();

// Form cần xác nhận (xoá địa chỉ…).
document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
});

dispatchInlineCartChange();

mountSearchSuggestions(t);

function productViewViewerId() {
    try {
        const key = 'vani.product_viewer';
        const previous = JSON.parse(localStorage.getItem(key) || 'null');
        if (/^[a-f0-9]{32}$/.test(previous?.id) && previous.expiresAt > Date.now()) return previous.id;

        const id = Array.from(crypto.getRandomValues(new Uint8Array(16)), byte => byte.toString(16).padStart(2, '0')).join('');
        localStorage.setItem(key, JSON.stringify({ id, expiresAt: Date.now() + 24 * 60 * 60 * 1000 }));
        return id;
    } catch {
        return null;
    }
}

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
    const viewerId = productViewViewerId();
    if (viewerId) body.append('viewer_id', viewerId);
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
                        image.width = 300;
                        image.height = 400;
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

session.mount({ updateCartCount, dispatchCartChange });

mountMiniCart();

mountProductCart();

session.mountLoginForm(firstError);

mountListingNavigation();

/* ── Thông báo dùng chung: bấm nút đóng → ẩn thông báo ───────────────────────── */
document.addEventListener('click', (event) => {
    const close = event.target.closest('button[data-vani-notification-close]');
    if (!close) return;
    const item = close.closest('[data-vani-notification]');
    if (!item) return;
    item.classList.add('vani-notification-closing');
    item.addEventListener('animationend', () => item.remove(), { once: true });
});

/* ── ToastManager: thông báo tự động biến mất (top-right, stackable) ────────── */
const ToastManager = (() => {
    const container = document.querySelector('[data-vani-toast-container]');
    if (!container) return null;

    let nextIndex = 0;
    const timers = new Map();

    const clearTimer = (id) => {
        const timer = timers.get(id);
        if (timer) {
            clearTimeout(timer);
            timers.delete(id);
        }
    };

    const dismiss = (el) => {
        if (!el || !el.isConnected) return;
        clearTimer(Number(el.dataset.vaniToastId));
        el.classList.add('vani-toast-closing');
        el.addEventListener('animationend', () => el.remove(), { once: true });
    };

    const show = (message, type = 'info', duration = 5000) => {
        const el = document.createElement('div');
        const id = nextIndex++;
        el.id = `vani-toast-js-${id}`;
        el.className = `vani-toast mb-2 rounded-[var(--radius-theme)] border p-3 text-sm leading-relaxed shadow-lg
            ${type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800'
            : type === 'error' ? 'bg-red-50 border-red-200 text-red-700'
            : type === 'warning' ? 'bg-amber-50 border-amber-200 text-amber-800'
            : 'bg-blue-50 border-blue-200 text-blue-800'}`;
        el.setAttribute('role', type === 'error' ? 'alert' : 'status');
        el.setAttribute('aria-live', 'polite');
        el.setAttribute('data-vani-toast', '');
        el.setAttribute('data-vani-toast-id', id);
        el.setAttribute('data-vani-toast-duration', duration);
        el.innerHTML = `
            <div class="flex items-start gap-3">
                <p>${message}</p>
                <button type="button"
                        class="mt-0.5 shrink-0 inline-flex items-center rounded text-current hover:bg-current hover:text-white opacity-70 hover:opacity-100"
                        data-vani-toast-close
                        aria-label="${t('close')}">
                    ×
                </button>
            </div>`;
        container.appendChild(el);

        const timer = setTimeout(() => dismiss(el), duration);
        timers.set(id, timer);
        return el;
    };

    // Bấm nút đóng trên mỗi toast
    container.addEventListener('click', (event) => {
        const close = event.target.closest('button[data-vani-toast-close]');
        if (!close) return;
        const el = close.closest('[data-vani-toast]');
        if (el) dismiss(el);
    });

    return { show, dismiss };
})();

// Expose cho plugin / theme con sử dụng: window.vani.toast.show(msg, type, duration)
if (typeof window.vani === 'undefined') {
    window.vani = {};
}
window.vani.toast = ToastManager;
