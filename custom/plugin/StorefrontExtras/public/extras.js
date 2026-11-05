// vani.storefront-extras — tương tác của khối (HTML chạy được khi tắt JS; JS chỉ làm tốt hơn).
(function () {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // "Đã xem": danh sách trong trình duyệt do theme ghi ở trang sản phẩm (localStorage `vani.recently_viewed`).
    let viewed = [];
    try {
        viewed = (JSON.parse(localStorage.getItem('vani.recently_viewed') || '[]') || []).map(Number).filter((id) => id > 0);
    } catch {
        viewed = [];
    }
    document.querySelectorAll('[data-extras-recent]').forEach((box) => {
        if (!viewed.length) return;
        const exclude = new Set((box.dataset.excludeSlugs || '').split(',').filter(Boolean));
        const limit = Number(box.dataset.limit) || 8;
        fetch(`${box.dataset.productsUrl}?ids=${viewed.slice(0, 30).join(',')}`, { headers: { Accept: 'application/json', 'X-Vani-Locale': box.dataset.locale || '' } })
            .then((response) => (response.ok ? response.json() : null))
            .then((json) => {
                const products = (json?.data || []).filter((product) => !exclude.has(product.slug)).slice(0, limit);
                if (!products.length) return;
                const list = box.querySelector('[data-extras-recent-list]');
                const scroll = list.dataset.layout === 'scroll';
                list.replaceChildren(
                    ...products.map((product) => {
                        const link = document.createElement('a');
                        link.href = box.dataset.productUrl.replace('__slug__', encodeURIComponent(product.slug));
                        link.className = scroll ? 'block w-40 shrink-0 snap-start text-sm' : 'block text-sm';
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
                    }),
                );
                box.hidden = false;
            })
            .catch(() => {});
    });

    // Trình chiếu: tự chuyển theo data-interval (giây), dừng khi rê chuột/focus; tắt khi "giảm chuyển động".
    document.querySelectorAll('[data-extras-slideshow]').forEach((show) => {
        const track = show.querySelector('[data-extras-slides]');
        const seconds = Number(show.dataset.interval) || 0;
        if (!track || seconds <= 0 || reduceMotion || track.children.length < 2) return;
        let paused = false;
        ['mouseenter', 'focusin'].forEach((type) => show.addEventListener(type, () => (paused = true)));
        ['mouseleave', 'focusout'].forEach((type) => show.addEventListener(type, () => (paused = false)));
        setInterval(() => {
            if (paused || document.hidden) return;
            const next = Math.round(track.scrollLeft / track.clientWidth) + 1;
            track.scrollTo({ left: next >= track.children.length ? 0 : next * track.clientWidth, behavior: 'smooth' });
        }, seconds * 1000);
    });

    // Sản phẩm theo tab: không JS → xếp dọc; có JS → chuyển tab.
    document.querySelectorAll('[data-extras-tabs]').forEach((block) => {
        const tabs = [...block.querySelectorAll('[data-extras-tab]')];
        const panels = [...block.querySelectorAll('[data-extras-panel]')];
        if (tabs.length < 2) return;
        block.querySelector('[data-extras-tablist]')?.classList.replace('hidden', 'flex');
        block.querySelectorAll('[data-extras-panel-title]').forEach((title) => (title.hidden = true));
        const select = (index) => {
            tabs.forEach((tab, i) => tab.setAttribute('aria-selected', i === index ? 'true' : 'false'));
            panels.forEach((panel, i) => (panel.hidden = i !== index));
        };
        tabs.forEach((tab, i) => tab.addEventListener('click', () => select(i)));
        select(0);
    });

    // Video hero: nút phát/dừng; "giảm chuyển động" → không tự phát.
    document.querySelectorAll('[data-extras-video]').forEach((video) => {
        const toggle = video.parentElement.querySelector('[data-extras-video-toggle]');
        const label = () => {
            if (toggle) toggle.textContent = video.paused ? toggle.dataset.labelPlay : toggle.dataset.labelPause;
        };
        if (reduceMotion && video.autoplay) {
            video.pause();
            video.removeAttribute('autoplay');
        }
        toggle?.addEventListener('click', () => {
            video.paused ? video.play().catch(() => {}) : video.pause();
        });
        video.addEventListener('play', label);
        video.addEventListener('pause', label);
        label();
    });
})();
