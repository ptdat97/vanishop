# vani.zalo-zns

Tin giao dịch và OTP qua **Zalo Notification Service (ZNS)**:

- Kênh `zns` của Notification. ZNS chỉ gửi theo **template đã được Zalo duyệt**, nên mẫu tin kênh `zns` trong
  Admin → Thông báo khai báo tham số thay cho nội dung:
  `{"template_id": "231234", "params": {"customer_name": "{{ customer_name }}", "order_code": "{{ order_number }}"}}`.
- `OtpSender` kênh `zns` (priority 60, cao hơn SMS vì rẻ hơn). SĐT không dùng Zalo → lỗi → Core tự chuyển sang
  kênh kế tiếp (vd. `vani.sms-brandname`).

## Cấu hình (`.env`)

| Biến | Ý nghĩa |
|---|---|
| `ZALO_APP_ID`, `ZALO_APP_SECRET` | Ứng dụng Zalo liên kết OA |
| `ZALO_REFRESH_TOKEN` | Refresh token ban đầu. Zalo trả refresh token mới mỗi lần làm mới; plugin lưu bản mới nhất trong cache (`ZALO_TOKEN_CACHE_STORE`, nên là store bền như `database`/`redis`) |
| `ZALO_ZNS_MODE` | `development` = chỉ gửi tới tester của OA |
| `ZALO_ZNS_OTP_TEMPLATE_ID`, `ZALO_ZNS_OTP_PARAM` | Mẫu ZNS OTP và tên tham số chứa mã |

`tracking_id` = id nhật ký gửi (`vani-<id>`), giữ nguyên giữa các lần thử lại.

Phân loại lỗi: `0` = thành công; lỗi token (`-124`, `-216`) → làm mới token và gửi lại một lần; `-32`, HTTP 5xx/429, timeout → thử lại; mã khác (SĐT không có Zalo, template sai…) → lỗi vĩnh viễn.
