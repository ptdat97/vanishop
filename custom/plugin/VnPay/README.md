# vani.vnpay

Cổng thanh toán VNPay, giao thức v2.1.0 (`kind: payment_gateway`). Khách thanh toán được bằng thẻ ATM nội địa, thẻ quốc tế, QR hoặc ví VNPay.

## Cấu hình

Vào Admin → Cấu hình → `vani.vnpay`. Giá trị để trống thì lấy theo `.env`.

| Khoá | Ý nghĩa | `.env` |
|---|---|---|
| `tmn_code` | Mã website VNPay cấp | `VNPAY_TMN_CODE` |
| `hash_secret` | Khoá bí mật, lưu mã hoá | `VNPAY_HASH_SECRET` |
| `sandbox` | Dùng môi trường thử `sandbox.vnpayment.vn`. Tắt khi chạy thật | `VNPAY_SANDBOX` |
| `ttl` | Thời gian chờ thanh toán (giây), tối thiểu 300 | `VNPAY_TTL` |
| — | Return URL cho storefront headless | `VNPAY_RETURN_URL` |

Đăng ký với VNPay:
- **IPN URL:** `https://<domain>/api/payments/vnpay/callback`
- **Return URL** (storefront native): `https://<domain>/p/vani-vnpay/return`

## Luồng thanh toán

1. **Đặt hàng:** Core tạo payment, cổng trả URL VNPay đã ký.
   - `vnp_TxnRef` là public id của payment; `vnp_Amount` = số tiền × 100.
   - Thời điểm tạo được lưu trong `gateway_reference`, nên `querydr`/`refund` luôn gửi đúng `vnp_TransactionDate`.
   - Checkout native chuyển khách sang VNPay ngay sau khi đặt.
2. **IPN** (VNPay gọi GET về server): Core chỉ ghi nhận thanh toán từ IPN đã xác minh chữ ký và đúng số tiền. Phản hồi luôn là HTTP 200 kèm `RspCode`:

| Kết quả | `RspCode` |
|---|---|
| Đã ghi nhận | `00` |
| Đã xác nhận trước đó | `02` |
| Sai số tiền | `04` |
| Không thấy đơn | `01` |
| Sai chữ ký | `97` |

3. **Return URL:** chỉ báo kết quả cho khách rồi chuyển về trang đơn, không ghi nhận thanh toán. Trang đơn đọc trạng thái mới nhất: chưa trả thì có nút "Thanh toán ngay", đã nhận IPN thì hiện "Đã nhận thanh toán".
4. **Tra cứu** (`querydr`): lệnh đối soát định kỳ của Core dùng để bù khi IPN bị lỡ.
5. **Hoàn tiền** (`refund`, toàn phần hoặc một phần):
   - Plugin tra cứu giao dịch gốc để lấy `vnp_TransactionNo` rồi mới gửi lệnh hoàn.
   - Idempotent theo khoá của Core: lưu ở bảng `plg_vnpay_refunds`, `vnp_RequestId` cố định theo khoá.
   - VNPay từ chối, hoặc phản hồi không xác minh được chữ ký, thì coi là thất bại để nhân viên hoàn tay.

## Trước khi go-live

- **Chưa chạy với TMN sandbox thật:** chữ ký URL thanh toán và IPN theo tài liệu VNPay. Thứ tự trường của `querydr`/`refund` (gửi đi và phản hồi) viết theo tài liệu v2.1.0 nhưng chưa đối chiếu với sandbox. Nếu lệch, plugin an toàn theo hướng thất bại: tra cứu trả "chờ", hoàn tiền trả "thất bại", không ghi nhận sai.
- **Cần làm với tài khoản sandbox:**
  - Thanh toán thẻ test NCB.
  - Huỷ ở trang VNPay (mã 24).
  - IPN gửi lặp.
  - `querydr` sau khi thanh toán.
  - Hoàn một phần và toàn phần.
- **Số tiền:** VNPay nhận từ 5.000 ₫ đến dưới 1 tỷ ₫. Ngoài khoảng này cổng không hiện ở checkout.
