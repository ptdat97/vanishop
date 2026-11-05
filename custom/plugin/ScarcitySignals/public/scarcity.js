// vani.scarcity-signals: hiện "Chỉ còn N" theo size đang chọn trên trang sản phẩm. Số lấy từ HTML (đã tính ở server).
(() => {
    for (const box of document.querySelectorAll('[data-scarcity-left]')) {
        if (box.dataset.ready) continue;
        box.dataset.ready = '1';
        let left = {};
        try {
            left = JSON.parse(box.dataset.scarcityLeft || '{}') || {};
        } catch {
            continue;
        }
        const form = box.closest('form') || document;
        const update = () => {
            const chosen = form.querySelector('input[name="variant_id"]:checked');
            const count = chosen ? left[chosen.value] : undefined;
            box.hidden = !count;
            if (count) box.textContent = box.dataset.template.replace('__N__', String(count));
        };
        form.addEventListener('change', (event) => {
            if (event.target instanceof HTMLInputElement && event.target.name === 'variant_id') update();
        });
        update();
    }
})();
