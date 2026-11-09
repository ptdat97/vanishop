import { createIcons, Heart, Menu, Search, ShoppingBag, UserRound } from 'lucide';

/*
 * Đảo tương tác của vani-base (ADR-025): JS chỉ tăng cường, trang dùng được khi JS tắt.
 * Thư viện giao diện (Motion, Swiper, PhotoSwipe) chỉ tải khi trang cần (loadVendor bên dưới).
 * Dữ liệu đọc từ data-* đã render sẵn ở server.
 */
createIcons({ icons: { Heart, Menu, Search, ShoppingBag, UserRound } });
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
 * Gọi chính các route web của storefront với `Accept: application/json` (cùng phiên, validation, throttle như form; không JS
 * thì form vẫn chạy). URL theo ngôn ngữ trang lấy từ `script[data-vani-urls]`. Lỗi: Error có `status`, `errors` (theo trường),
 * `code`. Theme con / plugin dùng lại:
 *   vani.cart.get() · add(variantId, quantity, options) · update(lineId, quantity) · remove(lineId) · select(lineIds)
 *   vani.cart.applyVoucher(code) · removeVoucher() · open(state?) · close()
 *   vani.auth.requestOtp(phone) · verify(code) · logout()
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
function activateMobileMenuPanel(menu, panel, focusTarget = null) {
    const panels = [...menu.querySelectorAll('[data-vani-menu-panel]')];
    const panelIndex = panels.indexOf(panel);
    if (panelIndex < 0) return;

    menu.querySelector('[data-vani-menu-track]')?.style.setProperty('--vani-menu-index', String(panelIndex));
    panels.forEach((currentPanel, index) => {
        const isActive = index === panelIndex;
        currentPanel.inert = !isActive;
        currentPanel.setAttribute('aria-hidden', String(!isActive));
    });
    menu.dataset.vaniMenuActive = panel.dataset.vaniMenuPanelId;
    focusTarget?.focus();
}
document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) return;
    const opener = event.target.closest('[data-vani-drawer-open], [data-vani-modal-open]');
    if (opener) {
        const dialogId = opener.dataset.vaniDrawerOpen || opener.dataset.vaniModalOpen;
        const dialog = document.getElementById(dialogId);
        if (dialog instanceof HTMLDialogElement && !dialog.open) {
            event.preventDefault();
            dialogOpeners.set(dialog, opener);
            if (dialog.matches('[data-vani-mobile-menu-dialog]')) {
                const menu = dialog.querySelector('[data-vani-mobile-menu]');
                const rootPanel = menu?.querySelector('[data-vani-menu-panel-level="0"]');
                if (menu && rootPanel) activateMobileMenuPanel(menu, rootPanel);
            }
            dialog.showModal();
        }
        return;
    }

    const nextMenuButton = event.target.closest('[data-vani-menu-next]');
    if (nextMenuButton) {
        const menu = nextMenuButton.closest('[data-vani-mobile-menu]');
        const targetId = nextMenuButton.dataset.vaniMenuTarget;
        const targetPanel = [...(menu?.querySelectorAll('[data-vani-menu-panel]') ?? [])]
            .find((panel) => panel.dataset.vaniMenuPanelId === targetId);
        const backButton = targetPanel?.querySelector('[data-vani-menu-back]');
        if (menu && targetPanel) activateMobileMenuPanel(menu, targetPanel, backButton);
        return;
    }

    const backMenuButton = event.target.closest('[data-vani-menu-back]');
    if (backMenuButton) {
        const menu = backMenuButton.closest('[data-vani-mobile-menu]');
        const targetId = backMenuButton.dataset.vaniMenuTarget;
        const targetPanel = [...(menu?.querySelectorAll('[data-vani-menu-panel]') ?? [])]
            .find((panel) => panel.dataset.vaniMenuPanelId === targetId);
        const openerButton = document.getElementById(backMenuButton.dataset.vaniMenuFocus);
        if (menu && targetPanel) activateMobileMenuPanel(menu, targetPanel, openerButton);
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

function updateCartCount(count) {
    const badge = document.querySelector('[data-vani-cart-count]');
    if (!badge) return;
    badge.textContent = count > 0 ? String(count) : '';
    badge.hidden = !(count > 0);
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
    requestOtp: (phone) => request('POST', urls.login_otp, { phone }),
    verify: (code) => request('POST', urls.login_verify, { code }),
    logout: () => request('POST', urls.logout),
};

const search = {
    suggest: (query) =>
        fetch(`${urls.search_suggestions}?${new URLSearchParams({ q: query })}`, { headers: { Accept: 'application/json', 'X-Vani-Locale': document.documentElement.lang || '' } })
            .then((response) => (response.ok ? response.json() : { data: null }))
            .then((json) => json.data),
    /** Trang danh sách (tìm kiếm, danh mục, thương hiệu, lọc, phân trang) không tải lại: lấy HTML (trang cache được), thay vùng [data-vani-listing]. */
    async listing(url, { push = true } = {}) {
        const current = document.querySelector('[data-vani-listing]');
        if (!current) {
            window.location.href = url;
            return;
        }
        current.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'text/html' } });
            if (!response.ok) throw new Error(String(response.status));
            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            const next = page.querySelector('[data-vani-listing]');
            if (!next) throw new Error('no listing');
            current.replaceWith(next);
            const heading = page.querySelector('h1');
            if (heading && document.querySelector('h1')) document.querySelector('h1').textContent = heading.textContent;
            document.title = page.title || document.title;
            if (push) history.pushState({ vaniListing: true }, '', url);
            next.scrollIntoView({ block: 'start', behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
            document.dispatchEvent(new CustomEvent('vani:content-updated', { detail: { element: next } }));
        } catch {
            window.location.href = url;
        }
    },
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

const motionElementSelector = '[data-vani-motion]';
const motionStaggerSelector = '[data-vani-motion-stagger]';
const motionItemSelector = '[data-vani-motion-item]';
if (
    document.querySelector(`${motionElementSelector}, ${motionStaggerSelector}`) &&
    !window.matchMedia('(prefers-reduced-motion: reduce)').matches
) {
    loadVendor('motion')
        .then(({ animate, inView }) => {
            const observed = new WeakSet();
            document.documentElement.classList.add('vani-motion-ready');

            const observeMotionTargets = (root) => {
                const findTargets = (selector) => {
                    const targets = [...root.querySelectorAll(selector)];
                    if (root instanceof Element && root.matches(selector)) {
                        targets.unshift(root);
                    }
                    return targets;
                };
                const staggerGroups = findTargets(motionStaggerSelector);
                const staggeredItems = new WeakSet();

                staggerGroups.forEach((group) => {
                    if (observed.has(group)) return;
                    const items = [...group.querySelectorAll(motionItemSelector)].filter((item) => item.closest(motionStaggerSelector) === group);
                    if (items.length === 0) return;

                    observed.add(group);
                    items.forEach((item) => {
                        staggeredItems.add(item);
                        item.setAttribute('data-vani-motion-pending', '');
                    });
                    inView(group, () => {
                        items.forEach((item, index) => {
                            animate(item, { opacity: [0, 1], y: [12, 0] }, {
                                duration: 0.65,
                                delay: Math.min(index, 6) * 0.045,
                                ease: [0.22, 1, 0.36, 1],
                            }).then(() => item.removeAttribute('data-vani-motion-pending'));
                        });
                    });
                });

                const targets = findTargets(motionElementSelector);
                targets.forEach((target) => {
                    const motionParent = target.closest(motionElementSelector);
                    if (observed.has(target) || staggeredItems.has(target) || (motionParent && motionParent !== target)) return;
                    observed.add(target);
                    const motion = target.dataset.vaniMotion ?? 'rise';
                    target.setAttribute('data-vani-motion-pending', '');
                    inView(target, () => {
                        const keyframes = motion === 'fade' ? { opacity: [0, 1] } : { opacity: [0, 1], y: [12, 0] };
                        animate(target, keyframes, {
                            duration: 0.7,
                            ease: [0.22, 1, 0.36, 1],
                        }).then(() => target.removeAttribute('data-vani-motion-pending'));
                    });
                });
            };

            observeMotionTargets(document);
            document.addEventListener('vani:content-updated', (event) => {
                if (event.detail?.element instanceof Element) {
                    observeMotionTargets(event.detail.element);
                }
            });
        })
        .catch((error) => {
            document.documentElement.classList.remove('vani-motion-ready');
            document.querySelectorAll('[data-vani-motion-pending]').forEach((target) => target.removeAttribute('data-vani-motion-pending'));
            console.error('Không thể tải Motion cho hiệu ứng sản phẩm.', error);
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
 * Không JS: liên kết mở ảnh lớn.
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
// Trang giỏ đầy đủ (0.3.94): sửa số lượng, xoá dòng, chọn dòng, mã giảm giá qua window.vani.cart — server trả `page_html`,
// thay vùng [data-vani-cart-page] (không tải lại trang). Gắn sự kiện ở vùng bao ngoài vì nội dung bên trong được thay.
// Không JS: các form POST như cũ (nút "Cập nhật"/"Áp dụng lựa chọn" chỉ hiện khi tắt JS — [data-no-js]).
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
    const account = document.querySelector('[data-vani-account]');
    if (account) {
        account.setAttribute('aria-label', session.account.label);
        account.setAttribute('title', session.account.label);
        account.setAttribute('href', session.account.url);
    }
    const count = document.querySelector('[data-vani-cart-count]');
    if (count && session.cart.count > 0) {
        count.textContent = String(session.cart.count);
        count.hidden = false;
    }
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

// Đăng nhập OTP không tải lại trang: gửi mã → hiện ô nhập mã; xác thực → về trang trước đó (hoặc tài khoản).
const otpForm = document.querySelector('form[data-vani-login-otp]');
const verifyForm = document.querySelector('form[data-vani-login-verify]');
if (otpForm && verifyForm) {
    const showError = (form, text) => {
        const box = form.querySelector('[data-vani-form-error]');
        if (box) {
            box.textContent = text || '';
            box.hidden = !text;
        }
    };
    otpForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        showError(otpForm, '');
        try {
            const result = await auth.requestOtp(String(new FormData(otpForm).get('phone') ?? ''));
            verifyForm.hidden = false;
            verifyForm.querySelectorAll('[data-vani-login-phone]').forEach((element) => (element.textContent = result.phone));
            const status = document.querySelector('[data-vani-flash="status"]');
            if (status) {
                status.textContent = result.message;
                status.hidden = false;
            }
            verifyForm.querySelector('input[name="code"]')?.focus();
        } catch (error) {
            showError(otpForm, firstError(error, 'phone'));
        }
    });
    verifyForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        showError(verifyForm, '');
        try {
            const result = await auth.verify(String(new FormData(verifyForm).get('code') ?? ''));
            window.location.href = result.redirect;
        } catch (error) {
            showError(verifyForm, firstError(error, 'code'));
        }
    });
}

// Danh sách sản phẩm: lọc và phân trang không tải lại trang (link thường vẫn chạy khi tắt JS; mở tab mới/ctrl+click giữ nguyên).
document.addEventListener('click', (event) => {
    const link = event.target.closest('[data-vani-listing] a[href]');
    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || link.target) return;
    const url = new URL(link.href, window.location.href);
    if (url.origin !== window.location.origin || url.pathname !== window.location.pathname) return;
    event.preventDefault();
    search.listing(url.href);
});
window.addEventListener('popstate', (event) => {
    if (event.state?.vaniListing || document.querySelector('[data-vani-listing]')) {
        search.listing(window.location.href, { push: false });
    }
});
if (document.querySelector('[data-vani-listing]')) {
    history.replaceState({ vaniListing: true }, '', window.location.href);
}
