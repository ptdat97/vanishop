/*
 * Đảo tương tác của vani-base (ADR-025): JS chỉ tăng cường, trang dùng được khi JS tắt.
 * Không phụ thuộc thư viện ngoài; dữ liệu đọc từ data-* đã render sẵn ở server.
 */
document.documentElement.classList.add('js');

// PDP: chọn màu → đổi ảnh chính theo bộ ảnh của màu (data-images trên fieldset màu).
document.querySelectorAll('[data-product-gallery]').forEach((gallery) => {
    const main = gallery.querySelector('[data-gallery-main]');
    const form = document.querySelector('[data-variant-form]');
    if (!main) {
        return;
    }

    // Ảnh là {url, alt} (cùng hình dạng Storefront API).
    const show = (url) => {
        main.src = url;
        gallery.querySelectorAll('[data-gallery-thumb]').forEach((thumb) => {
            thumb.classList.toggle('border-primary', thumb.dataset.src === url);
            thumb.classList.toggle('border-slate-200', thumb.dataset.src !== url);
        });
    };

    gallery.querySelectorAll('[data-gallery-thumb]').forEach((thumb) => thumb.addEventListener('click', () => show(thumb.dataset.src)));

    form?.addEventListener('change', (event) => {
        const group = event.target.closest('[data-color-group]');
        const images = group ? JSON.parse(group.dataset.images || '[]') : [];
        if (images.length > 0) {
            show(images[0].url ?? images[0]);
        }
    });
});

// Giỏ: đổi số lượng thì gửi form luôn (nút "Cập nhật" vẫn có cho trường hợp JS tắt).
document.querySelectorAll('[data-autosubmit]').forEach((input) => {
    input.addEventListener('change', () => input.form?.requestSubmit());
});

// Checkout: chọn tỉnh → tải phường/xã từ Storefront API (không JS: nút "Tải danh sách phường/xã").
document.querySelectorAll('[data-province-select]').forEach((province) => {
    const ward = province.form?.querySelector('[data-ward-select]');
    if (!ward) {
        return;
    }

    province.addEventListener('change', async () => {
        ward.replaceChildren(new Option('— Đang tải… —', ''));
        if (!province.value) {
            ward.replaceChildren(new Option('— Chọn tỉnh/thành trước —', ''));
            return;
        }
        try {
            const response = await fetch(`${province.dataset.wardsUrl}/${encodeURIComponent(province.value)}/wards`, {
                headers: { Accept: 'application/json' },
            });
            const { data } = await response.json();
            ward.replaceChildren(new Option('— Chọn phường/xã —', ''), ...data.map((item) => new Option(item.name, item.code)));
        } catch {
            ward.replaceChildren(new Option('— Không tải được, thử lại —', ''));
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
