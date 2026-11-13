// vani.scarcity-signals: hiện "Chỉ còn N" theo SKU đang chọn trên trang sản phẩm (bộ chọn 1–3 trục: nút hoặc danh sách). Số lấy từ HTML (đã tính ở server).
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
            const chosen = form.querySelector('input[name="variant_id"]:checked, select[name="variant_id"]');
            const count = chosen ? left[chosen.value] : undefined;
            box.hidden = !count;
            if (count) box.textContent = box.dataset.template.replace('__N__', String(count));
        };
        form.addEventListener('change', (event) => {
            if ((event.target instanceof HTMLInputElement || event.target instanceof HTMLSelectElement) && event.target.name === 'variant_id') update();
        });
        update();
    }
})();
