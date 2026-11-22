export function mountProduct({ loadVendor, mountCarousel, mountLightbox }) {
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
                slide?.parentElement.scrollTo({ left: slide.offsetLeft, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
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
        const selectedVariant = () => form.querySelector('input[type="hidden"][name="variant_id"], input[name="variant_id"]:checked, select[name="variant_id"]');
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
