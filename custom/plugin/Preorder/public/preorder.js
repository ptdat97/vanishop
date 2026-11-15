// vani.preorder: hiện "Đặt trước — dự kiến có hàng" theo SKU đang chọn trên trang sản phẩm. Ngày lấy từ HTML (đã tính ở server).
(() => {
    for (const box of document.querySelectorAll('[data-preorder]')) {
        if (box.dataset.ready) continue;
        box.dataset.ready = '1';
        let dates = {};
        try {
            dates = JSON.parse(box.dataset.preorder || '{}') || {};
        } catch {
            continue;
        }
        const form = box.closest('form') || document;
        const update = () => {
            const chosen = form.querySelector('input[name="variant_id"]:checked, select[name="variant_id"]');
            const inPreorder = chosen && Object.prototype.hasOwnProperty.call(dates, chosen.value);
            box.hidden = !inPreorder;
            if (inPreorder) box.textContent = dates[chosen.value] ? box.dataset.template.replace('__D__', dates[chosen.value]) : box.dataset.undated;
        };
        form.addEventListener('change', (event) => {
            if ((event.target instanceof HTMLInputElement || event.target instanceof HTMLSelectElement) && event.target.name === 'variant_id') update();
        });
        update();
    }
})();
