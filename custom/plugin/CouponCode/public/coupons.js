/*
 * vani.coupon-code: nút "Dùng" / "Chọn mã tốt nhất" của ví voucher và nút "Sao chép mã". Gắn một lần cho cả trang (event
 * delegation): vùng ví được dựng lại khi giỏ đổi (page_html) thì nút mới vẫn chạy.
 *  - Trang checkout: điền mã vào ô mã của Core rồi bấm "Áp dụng" (tính lại báo giá như khách tự nhập).
 *  - Trang giỏ: window.vani.cart.applyVoucher(code) — Core lưu mã và dựng lại trang giỏ.
 */
(() => {
    if (window.vaniCouponCode) {
        return;
    }
    window.vaniCouponCode = true;
    const copiedText = document.querySelector('script[data-vani-coupon-script][data-copied]')?.dataset.copied ?? '';
    const toast = (message, type = 'success') => window.vani?.toast?.show?.(message, type);

    document.addEventListener('click', (event) => {
        const use = event.target.closest('[data-vani-coupon-use]');
        if (use) {
            event.preventDefault();
            const code = use.dataset.vaniCouponUse;
            const checkoutInput = document.getElementById('voucher_code');
            const quote = checkoutInput?.form?.querySelector('button[name="action"][value="quote"]');
            if (checkoutInput && quote) {
                checkoutInput.value = code;
                quote.click();
                return;
            }
            use.disabled = true;
            window.vani?.cart
                ?.applyVoucher(code)
                .catch((error) => toast(error.errors?.code?.[0] ?? error.message, 'error'))
                .finally(() => {
                    use.disabled = false;
                });
            return;
        }
        const copy = event.target.closest('[data-vani-coupon-copy]');
        if (copy) {
            event.preventDefault();
            navigator.clipboard?.writeText(copy.dataset.vaniCouponCopy).then(() => toast(copiedText));
        }
    });
})();
