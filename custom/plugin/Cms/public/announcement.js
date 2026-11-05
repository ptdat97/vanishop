// Thanh thông báo vani.cms (store-content §3.1): xoay vòng các dòng, nút trước/sau, dừng khi rê chuột,
// tôn trọng prefers-reduced-motion (không tự xoay), ẩn khi đã quá giờ kết thúc (trang cache CDN).
(() => {
    for (const bar of document.querySelectorAll('[data-vani-cms-announcement]')) {
        if (bar.dataset.ready) continue;
        bar.dataset.ready = '1';
        const until = Number(bar.dataset.until || 0);
        if (until && Date.now() / 1000 >= until) {
            bar.hidden = true;
            continue;
        }
        const lines = [...bar.querySelectorAll('[data-line]')];
        if (lines.length < 2) continue;
        const pause = Number(bar.dataset.pause || 3000);
        const fade = Number(bar.dataset.fade || 0);
        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        let current = 0;
        let timer = null;
        let hovering = false;

        const show = (index) => {
            const next = (index + lines.length) % lines.length;
            if (next === current) return;
            const previous = lines[current];
            current = next;
            const reveal = () => {
                previous.hidden = true;
                lines[current].hidden = false;
                if (fade && !reduced) {
                    lines[current].animate([{ opacity: 0 }, { opacity: 1 }], { duration: fade / 2 });
                }
            };
            if (fade && !reduced && previous.animate) {
                previous.animate([{ opacity: 1 }, { opacity: 0 }], { duration: fade / 2 }).onfinish = reveal;
            } else {
                reveal();
            }
        };
        const schedule = () => {
            clearTimeout(timer);
            if (!reduced && !hovering) timer = setTimeout(() => { show(current + 1); schedule(); }, pause + fade);
        };

        for (const [selector, delta] of [['[data-prev]', -1], ['[data-next]', 1]]) {
            const button = bar.querySelector(selector);
            if (!button) continue;
            button.hidden = false;
            button.addEventListener('click', () => { show(current + delta); schedule(); });
        }
        bar.addEventListener('mouseenter', () => { hovering = true; clearTimeout(timer); });
        bar.addEventListener('mouseleave', () => { hovering = false; schedule(); });
        bar.addEventListener('focusin', () => { hovering = true; clearTimeout(timer); });
        bar.addEventListener('focusout', () => { hovering = false; schedule(); });
        schedule();
    }
})();
