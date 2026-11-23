export function mountProduct({ loadVendor, mountLightbox }) {
    const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
    document.querySelectorAll('[data-product-gallery]').forEach((gallery) => {
        const slider = gallery.querySelector('[data-gallery-slider]');
        const track = slider?.querySelector('[data-gallery-track]');
        if (!track) return;

        const form = document.querySelector('[data-variant-form]');
        const thumbs = [...gallery.querySelectorAll('[data-gallery-thumb]')];
        const slides = [...track.querySelectorAll('[data-gallery-slide]')];
        const previous = slider.querySelector('[data-gallery-previous]');
        const next = slider.querySelector('[data-gallery-next]');
        const count = slider.querySelector('[data-gallery-position]');
        let current = 0;
        let frame;
        let lightbox;
        let loadingZoom = false;

        const highlight = (index, keepThumbVisible = true) => {
            current = index;
            if (previous) previous.disabled = index === 0;
            if (next) next.disabled = index === slides.length - 1;
            if (count) count.textContent = String(index + 1);
            thumbs.forEach((thumb) => {
                const active = Number(thumb.dataset.index) === index;
                thumb.classList.toggle('border-primary', active);
                thumb.classList.toggle('border-slate-200', !active);
                thumb.setAttribute('aria-current', active ? 'true' : 'false');
                if (!active || !keepThumbVisible) return;
                const rail = thumb.parentElement;
                const bounds = rail.getBoundingClientRect();
                const image = thumb.getBoundingClientRect();
                if (image.left < bounds.left) rail.scrollBy({ left: image.left - bounds.left });
                if (image.right > bounds.right) rail.scrollBy({ left: image.right - bounds.right });
            });
        };
        const go = (index, animate = true) => {
            if (!slides[index]) return;
            const left = track.scrollLeft + slides[index].getBoundingClientRect().left - track.getBoundingClientRect().left;
            track.scrollTo({ left, behavior: animate && !motion.matches ? 'smooth' : 'instant' });
            if (!animate || motion.matches) highlight(index);
        };

        track.addEventListener('scroll', () => {
            if (frame) return;
            frame = requestAnimationFrame(() => {
                frame = undefined;
                const index = Math.max(0, Math.min(slides.length - 1, Math.round(track.scrollLeft / track.clientWidth)));
                if (index !== current) highlight(index);
            });
        }, { passive: true });
        previous?.addEventListener('click', () => go(current - 1, false));
        next?.addEventListener('click', () => go(current + 1, false));
        highlight(0, false);
        thumbs.forEach((thumb) => thumb.addEventListener('click', () => go(Number(thumb.dataset.index))));
        form?.addEventListener('change', (event) => {
            const choice = event.target.tagName === 'SELECT' ? event.target.selectedOptions[0] : event.target;
            if (!choice?.dataset.images) return;
            const images = JSON.parse(choice.dataset.images || '[]');
            const first = images[0]?.url ?? images[0];
            if (first) go(thumbs.findIndex((thumb) => thumb.dataset.src === first));
        });

        slider.addEventListener('click', async (event) => {
            const link = event.target.closest('a[data-pswp-width]');
            if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || lightbox) return;
            event.preventDefault();
            if (loadingZoom) return;
            loadingZoom = true;
            try {
                const [PhotoSwipeLightbox, { default: PhotoSwipe }] = await Promise.all([loadVendor('photoswipe'), import('photoswipe')]);
                lightbox = mountLightbox(PhotoSwipeLightbox, slider, (index) => go(index, false), PhotoSwipe);
                lightbox.loadAndOpen(slides.indexOf(link.closest('[data-gallery-slide]')), { gallery: slider });
            } catch {
                window.location.assign(link.href);
            } finally {
                loadingZoom = false;
            }
        });
    });

    // PDP bộ chọn 1–3 trục (ADR-045): đổi giá trị trục đầu thì bỏ chọn SKU của nhóm khác (nhóm đó đang ẩn) và trả giá về khoảng giá
    // của sản phẩm; chọn SKU thì hiện đúng giá của SKU (data-price-html). Nhóm SKU hiện theo CSS :has().
    document.querySelectorAll('form[data-variant-form]').forEach((form) => {
        const chosen = (element) => (element.tagName === 'SELECT' ? element.selectedOptions[0] : element);
        const selectedVariant = () => form.querySelector('input[type="hidden"][name="variant_id"], input[name="variant_id"]:checked, select[name="variant_id"]');
        // Chưa chọn đủ: báo ngay tại nhóm đang hiện (viền + thông báo + focus), không gửi lên server.
        form.addEventListener('submit', (event) => {
            if (selectedVariant() || !form.querySelector('[name="variant_id"]')) return;
            event.preventDefault();
            event.stopImmediatePropagation();
            const group = [...form.querySelectorAll('[data-option-group]')].find((fieldset) => fieldset.offsetParent !== null) ?? form.querySelector('[data-primary-option]');
            group?.setAttribute('aria-invalid', 'true');
            group?.setAttribute('aria-describedby', 'vani-variant-error');
            group?.classList.add('rounded', 'ring-2', 'ring-red-500', 'ring-offset-4');
            const error = form.querySelector('[data-vani-error-for="variant_id"]');
            if (error) {
                error.textContent = form.dataset.chooseMessage || error.textContent;
                error.hidden = false;
            }
            group?.querySelector('[name="variant_id"]:not(:disabled), input[data-primary]:not(:disabled), select')?.focus();
        });
        form.addEventListener('change', (event) => {
            if (event.target.name !== 'variant_id' && !chosen(event.target)?.hasAttribute('data-primary')) return;
            form.querySelectorAll('fieldset[aria-describedby="vani-variant-error"]').forEach((fieldset) => {
                fieldset.removeAttribute('aria-invalid');
                fieldset.removeAttribute('aria-describedby');
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

    const shortcut = document.querySelector('[data-pdp-shortcut]');
    const purchase = document.querySelector('[data-pdp-purchase]');
    if (shortcut && purchase && 'IntersectionObserver' in window) {
        const media = window.matchMedia('(width < 768px)');
        const sync = () => {
            const rect = purchase.getBoundingClientRect();
            shortcut.hidden = !media.matches || rect.top < window.innerHeight;
        };
        const observer = new IntersectionObserver(sync);
        observer.observe(purchase);
        media.addEventListener('change', sync);
        shortcut.querySelector('a')?.addEventListener('click', (event) => {
            event.preventDefault();
            const form = purchase.form;
            form?.scrollIntoView({ block: 'start' });
            form?.focus({ preventScroll: true });
            shortcut.hidden = true;
        });
        sync();
    }
}
