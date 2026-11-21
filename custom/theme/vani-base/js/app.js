import { createIcons, Heart, House, LayoutGrid, Menu, Search, ShoppingBag, Sparkles, UserRound } from 'lucide';
import { listing, mountListingNavigation } from './listing.js';
import { mountMobileCategoryNav } from './mobile-category-nav.js';

const accountMenuRoot = document.querySelector('[data-vani-account-menu-root]');
if (accountMenuRoot) {
    Promise.all([import('vue'), import('./AccountMenuDropdown.vue')])
        .then(([{ createApp }, { default: AccountMenuDropdown }]) => {
            const links = accountMenuRoot.querySelector('[data-vani-account-menu-links]');
            if (!links) return;

            createApp(AccountMenuDropdown, {
                label: accountMenuRoot.dataset.label ?? '',
                linksHtml: links.outerHTML,
            }).mount(accountMenuRoot);
        })
        .catch((error) => console.error('Không thể tải menu tài khoản.', error));
}

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
 *   vani.cart.get() · add(variantId, quantity, options) · update(lineId, quantity) · remove(lineId) · select(lineIds)
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

const lineUrl = (template, lineId) => String(template || '').replace('987654321', encodeURIComponent(lineId));
const firstError = (error, field) => (error.errors?.[field] || [])[0] || error.message;

const dialogOpeners = new WeakMap();
document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) return;
    const opener = event.target.closest('[data-vani-drawer-open], [data-vani-modal-open]');
    if (opener) {
        const dialogId = opener.dataset.vaniDrawerOpen || opener.dataset.vaniModalOpen;
        const dialog = document.getElementById(dialogId);
        if (dialog instanceof HTMLDialogElement && !dialog.open) {
            event.preventDefault();
            dialogOpeners.set(dialog, opener);
            dialog.showModal();
            // Menu di động: tải danh mục (Storefront API) ở lần mở đầu; lỗi thì lần mở sau thử lại.
            if (dialog.matches('[data-vani-mobile-menu-dialog]')) {
                mountMobileCategoryNav(dialog.querySelector('[data-vani-category-nav]'));
            }
        }
        return;
    }

    const closeButton = event.target.closest('[data-vani-drawer-close]');
    if (closeButton) {
        closeButton.closest('dialog.vani-drawer')?.close();
        return;
    }

    const modalCloseButton = event.target.closest('[data-vani-modal-close]');
    if (modalCloseButton) {
        modalCloseButton.closest('dialog.vani-modal')?.close();
        return;
    }

    if (event.target instanceof HTMLDialogElement && event.target.matches('.vani-drawer, .vani-modal')) {
        event.target.close();
    }
});
document.addEventListener('close', (event) => {
    if (!(event.target instanceof HTMLDialogElement) || !event.target.matches('.vani-drawer, .vani-modal')) return;
    dialogOpeners.get(event.target)?.focus();
    dialogOpeners.delete(event.target);
}, true);

// Có thể có nhiều nhãn số món (header + thanh điều hướng dưới của menu di động).
function updateCartCount(count) {
    document.querySelectorAll('[data-vani-cart-count]').forEach((badge) => {
        badge.textContent = count > 0 ? String(count) : '';
        badge.hidden = !(count > 0);
    });
}

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

const auth = {
    login: (email, password) => request('POST', urls.login_authenticate, { email, password }),
    logout: () => request('POST', urls.logout),
};

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
 * Trước khi thư viện tải xong: liên kết mở ảnh lớn.
 */
function mountLightbox(PhotoSwipeLightbox, gallery, onChange) {
    const lightbox = new PhotoSwipeLightbox({
        gallery,
        children: 'a[data-pswp-width]',
        pswpModule: () => import('photoswipe'),
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

// PDP: gallery Swiper + phóng to PhotoSwipe; bấm ảnh nhỏ / chọn giá trị hoặc SKU → tới ảnh đầu của lựa chọn (data-images:
// ảnh của SKU đầu tiên mang giá trị trục đầu, hoặc ảnh của chính SKU — ADR-045 ảnh theo SKU).
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
        const choice = event.target.tagName === 'SELECT' ? event.target.selectedOptions[0] : event.target;
        if (!choice?.dataset.images) return;
        const images = JSON.parse(choice.dataset.images || '[]');
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

// PDP bộ chọn 1–3 trục (ADR-045): đổi giá trị trục đầu thì bỏ chọn SKU của nhóm khác (nhóm đó đang ẩn) và trả giá về khoảng giá
// của sản phẩm; chọn SKU thì hiện đúng giá của SKU (data-price-html). Nhóm SKU hiện theo CSS :has().
document.querySelectorAll('form[data-variant-form]').forEach((form) => {
    const chosen = (element) => (element.tagName === 'SELECT' ? element.selectedOptions[0] : element);
    const selectedVariant = () => form.querySelector('input[name="variant_id"]:checked, select[name="variant_id"]');
    // Chưa chọn đủ: báo ngay tại nhóm đang hiện (viền + thông báo + focus), không gửi lên server.
    form.addEventListener('submit', (event) => {
        if (selectedVariant() || !form.querySelector('[name="variant_id"]')) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        const group = [...form.querySelectorAll('[data-option-group]')].find((fieldset) => fieldset.offsetParent !== null) ?? form.querySelector('[data-primary-option]');
        group?.setAttribute('aria-invalid', 'true');
        group?.classList.add('rounded', 'ring-2', 'ring-red-500', 'ring-offset-4');
        const error = form.querySelector('[data-vani-error-for="variant_id"]');
        if (error) {
            error.textContent = form.dataset.chooseMessage || error.textContent;
            error.hidden = false;
        }
        group?.querySelector('[name="variant_id"]:not(:disabled)')?.focus();
    });
    form.addEventListener('change', (event) => {
        if (event.target.name !== 'variant_id') return;
        form.querySelectorAll('[aria-invalid]').forEach((fieldset) => {
            fieldset.removeAttribute('aria-invalid');
            fieldset.classList.remove('rounded', 'ring-2', 'ring-red-500', 'ring-offset-4');
        });
        const error = form.querySelector('[data-vani-error-for="variant_id"]');
        if (error) error.hidden = true;
    });
    const price = document.querySelector('[data-product-price]');
    const rangeHtml = price?.innerHTML;
    form.addEventListener('change', (event) => {
        const input = event.target;
        const choice = chosen(input);
        if (input.name === 'variant_id') {
            if (price) price.innerHTML = choice?.dataset.priceHtml || rangeHtml || '';
        } else if (choice?.hasAttribute('data-primary')) {
            const code = choice.dataset.optionValue;
            form.querySelectorAll('input[name="variant_id"]:checked').forEach((radio) => {
                if (radio.closest('[data-option-group]')?.dataset.optionGroup !== code) radio.checked = false;
            });
            if (price && rangeHtml !== undefined) price.innerHTML = rangeHtml;
        }
    });
});

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
        ward.replaceChildren(
            new Option(matches.length ? t('choose_ward') : t('wards_no_match'), ''),
            ...matches.map((item) => new Option(item.label, item.value, false, item.value === selected)),
        );
        if (matches.length === 1) {
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
    const rememberWards = filter ? mountWardFilter(filter, ward) : () => {};

    province.vaniLoadWards = async (selected = '') => {
        ward.replaceChildren(new Option(t('wards_loading'), ''));
        if (!province.value) {
            ward.replaceChildren(new Option(t('province_first'), ''));
            rememberWards();
            return;
        }
        try {
            const response = await fetch(`${province.dataset.wardsUrl}/${encodeURIComponent(province.value)}/wards`, {
                headers: { Accept: 'application/json' },
            });
            const { data } = await response.json();
            ward.replaceChildren(new Option(t('choose_ward'), ''), ...data.map((item) => new Option(item.name, item.code, false, item.code === selected)));
        } catch {
            ward.replaceChildren(new Option(t('wards_failed'), ''));
        }
        rememberWards();
    };
    province.addEventListener('change', () => province.vaniLoadWards());
});

/*
 * Checkout (`form[data-checkout-form]`):
 * - Đổi phường/xã, phương thức giao hoặc thanh toán → tính lại như nút "Áp dụng" (POST `action=quote`), rồi thay khung
 *   tóm tắt, danh sách phương thức, thanh tổng ở đáy và `expected_total` bằng bản server vừa render — phí giao theo địa
 *   chỉ (plugin hãng vận chuyển) luôn khớp tổng khách thấy.
 * - "Địa chỉ đã lưu": điền người nhận + địa chỉ vào form.
 * - Có tóm tắt lỗi: đưa focus tới đó (trình đọc màn hình đọc ngay, điện thoại không phải cuộn tìm).
 */
const checkoutForm = document.querySelector('form[data-checkout-form]');
if (checkoutForm) {
    document.querySelector('[data-checkout-errors]')?.focus();

    let quoteController = null;
    const requote = async () => {
        quoteController?.abort();
        quoteController = new AbortController();
        const data = new FormData(checkoutForm);
        data.set('action', 'quote');
        const summary = checkoutForm.querySelector('[data-checkout-summary]');
        summary?.setAttribute('aria-busy', 'true');
        try {
            // `getAttribute`: nút `name="action"` (Áp dụng) che thuộc tính `form.action`.
            const response = await fetch(checkoutForm.getAttribute('action'), { method: 'POST', body: data, credentials: 'same-origin', headers: { Accept: 'text/html' }, signal: quoteController.signal });
            if (!response.ok) return;
            const next = new DOMParser().parseFromString(await response.text(), 'text/html');
            ['[data-checkout-summary]', '[data-checkout-shipping]', '[data-checkout-payment]', '[data-checkout-bar]'].forEach((selector) => {
                const fresh = next.querySelector(selector);
                const current = checkoutForm.querySelector(selector);
                if (fresh && current) current.replaceWith(document.importNode(fresh, true));
            });
            const total = next.querySelector('input[name="expected_total"]');
            if (total) checkoutForm.querySelector('input[name="expected_total"]').value = total.value;
            document.dispatchEvent(new CustomEvent('vani:content-updated', { detail: { element: checkoutForm } }));
        } catch (error) {
            if (error.name !== 'AbortError') console.error('Không tính lại được đơn hàng.', error);
        } finally {
            checkoutForm.querySelector('[data-checkout-summary]')?.removeAttribute('aria-busy');
        }
    };
    checkoutForm.addEventListener('change', (event) => {
        if (event.target.matches('[data-ward-select], input[name="shipping_method"], input[name="payment_method"]')) {
            requote();
        }
    });

    const saved = checkoutForm.querySelector('[data-checkout-saved-address]');
    if (saved) {
        checkoutForm.querySelector('[data-checkout-saved-address-wrap]').hidden = false;
        saved.addEventListener('change', async () => {
            const option = saved.selectedOptions[0];
            if (!option?.dataset.fields) return;
            const fields = JSON.parse(option.dataset.fields);
            const byName = (key) => checkoutForm.elements.namedItem(key.replace(/^(\w+)\.(\w+)$/, '$1[$2]'));
            ['contact.full_name', 'contact.phone', 'shipping_address.street_line', 'shipping_address.province_name', 'shipping_address.ward_name'].forEach((key) => {
                const input = byName(key);
                if (input && fields[key] !== undefined) input.value = fields[key];
            });
            const province = checkoutForm.querySelector('[data-province-select]');
            if (province && fields['shipping_address.province_code']) {
                province.value = fields['shipping_address.province_code'];
                await province.vaniLoadWards?.(fields['shipping_address.ward_code'] ?? '');
            }
            requote();
        });
    }
}

// Form cần xác nhận (xoá địa chỉ…).
document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
});

// Trang cache được (không phiên, storefront §5): lấy phần riêng của khách — đăng nhập, số món trong giỏ, thông báo/lỗi
// của lần gửi trước.
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
        offcanvasBody?.querySelectorAll('button, input').forEach((element) => (element.disabled = true));
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

// Trang sản phẩm: "Thêm vào giỏ" không tải lại trang → mở giỏ mini; "Mua ngay" → checkout. Lỗi lạ (vd. cửa hàng ẩn giá cần
// đăng nhập): gửi form như thường để server chuyển hướng.
document.querySelectorAll('form[data-variant-form]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        if (!urls.cart_add || form.dataset.vaniSubmitting) return;
        event.preventDefault();
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
            form.dataset.vaniSubmitting = '1';
            if (submitter?.name) {
                const hidden = Object.assign(document.createElement('input'), { type: 'hidden', name: submitter.name, value: submitter.value });
                form.append(hidden);
            }
            form.submit();
        } finally {
            submitter?.removeAttribute('aria-busy');
        }
    });
});

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
