/*
 * Đảo tương tác của vani-base (ADR-025): JS chỉ tăng cường, trang dùng được khi JS tắt.
 * Không phụ thuộc thư viện ngoài; dữ liệu đọc từ data-* đã render sẵn ở server.
 */
document.documentElement.classList.add('js');

// PDP: chọn màu → đổi ảnh chính theo bộ ảnh của màu (data-images trên fieldset màu).
document.querySelectorAll('[data-product-gallery]').forEach((gallery) => {
    const main = gallery.querySelector('[data-gallery-main]');
    const form = document.querySelector('[data-variant-form]');
    if (!main || !form) {
        return;
    }

    form.addEventListener('change', (event) => {
        const group = event.target.closest('[data-color-group]');
        const images = group ? JSON.parse(group.dataset.images || '[]') : [];
        if (images.length > 0) {
            main.src = images[0];
        }
    });
});

// Giỏ: đổi số lượng thì gửi form luôn (nút "Cập nhật" vẫn có cho trường hợp JS tắt).
document.querySelectorAll('[data-autosubmit]').forEach((input) => {
    input.addEventListener('change', () => input.form?.requestSubmit());
});
