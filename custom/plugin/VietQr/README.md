# vani.vietqr

Plugin cổng thanh toán VietQR động cho VaniShop, minh hoạ extension point `vani.payment.gateways`.

## Tính năng

- Tạo URL / mã QuickLink VietQR theo chuẩn Napas 247 tương ứng với số tiền và số đơn hàng.
- Endpoint webhook/IPN tự động (`/api/payments/vietqr/callback`) xác minh chữ ký HMAC-SHA256 để ghi nhận thanh toán tự động vào Commerce Core.
- Chỉ khả dụng khi có cả tài khoản nhận tiền và khoá ký callback `VIETQR_SECRET` (khoá trống → không hiện ở checkout, mọi callback bị từ chối).
- Không tra cứu, không hoàn tiền qua cổng: khoản hoàn chờ nhân viên chuyển trả (hoặc kênh `RefundPayout`).
- Tuân thủ bộ kiểm thử chuẩn `PaymentGatewayContract`.

## Cài đặt & Kích hoạt

```bash
php artisan vani:plugin:install vani.vietqr
php artisan vani:plugin:enable vani.vietqr
```
