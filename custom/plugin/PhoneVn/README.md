# vani.phone-vn

Plugin hệ thống (`"bundled": true`): luật số điện thoại Việt Nam, cung cấp `Modules\Shared\Contracts\PhoneNumberPolicy` mã `vn` (chọn bằng `vanishop.locale.phone_policy` / `VANI_PHONE_POLICY`, mặc định `vn`).

- Nhận: `0912345678`, `84912345678`, `+84 912 345 678`, `(+84) 912-345-678`, số bàn `02438123456`. Lưu E.164 (`+84…`), hiển thị `0…`.
- Dùng ở: đăng nhập OTP, checkout, tra cứu đơn, sổ địa chỉ, tìm khách/đơn trong Admin.
- Tắt plugin (hoặc cấu hình mã khác) → Core dùng `international`: chỉ nhận số dạng `+…`/`00…`. Số đã lưu (E.164) không đổi.
