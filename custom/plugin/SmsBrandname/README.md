# vani.sms-brandname

SMS brandname qua **eSMS** cho VaniShop:

- Kênh `sms` của Notification (`NotificationChannel`): gửi tin giao dịch theo mẫu tin kênh `sms` trong Admin → Thông báo.
- `OtpSender` kênh `sms` (priority 50) cho đăng nhập khách hàng.

## Cấu hình (`.env`)

| Biến | Ý nghĩa |
|---|---|
| `ESMS_API_KEY`, `ESMS_SECRET_KEY` | Khoá API eSMS |
| `ESMS_BRANDNAME` | Brandname mặc định; brandname theo brand: `config/…/brandnames` (mã brand ⇒ brandname) |
| `ESMS_SANDBOX` | `true` = sandbox, không gửi thật |
| `ESMS_OTP_MESSAGE` | Mẫu OTP đã đăng ký với nhà mạng, `{code}` là mã |
| `ESMS_OTP_ENABLED` | Tắt để chỉ dùng cho thông báo |

Nội dung luôn được bỏ dấu trước khi gửi. `RequestId` = khoá idempotency của tin để retry không gửi trùng.

Phân loại lỗi: `100` = thành công; `99`, `103`, HTTP 5xx/429, timeout = thử lại; mã khác = lỗi vĩnh viễn (không thử lại).

Bật: `php artisan vani:plugin:install vani.sms-brandname && php artisan vani:plugin:enable vani.sms-brandname --scope=owner`.
