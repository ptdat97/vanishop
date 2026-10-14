# Đặc tả: Thanh tiến độ miễn phí giao hàng (bổ sung `vani.shipping-flat-rate` 1.1)

> Trạng thái: **Đặc tả — Owner đã duyệt 2026-10-14** (M0). Nguồn: plugin FreeShippingProgress 1.0.0 của VaniCommerce (Owner). Đợt: M1. Không tạo plugin mới: gộp vào plugin hệ thống `vani.shipping-flat-rate`, nơi đã có ngưỡng miễn phí giao (`free_over`).

## 1. Mục tiêu

Cho khách thấy còn thiếu bao nhiêu để được miễn phí giao, khuyến khích mua thêm.

## 2. Kiểm kê plugin cũ

| Tính năng | Giữ / đổi |
|---|---|
| Ngưỡng miễn phí giao riêng của plugin, tự hạ phí giao về 0 khi đạt | **Bỏ**: VaniShop dùng ngưỡng `free_over` của `vani.shipping-flat-rate` — một nguồn duy nhất cho cả phí và thanh tiến độ |
| Thông báo khi chưa đạt (chèn số tiền còn thiếu vào chỗ `{amount}`) và khi đã đạt | Giữ (mặc định: "Hãy thêm {amount} để được miễn phí vận chuyển", "Tuyệt vời! Đơn hàng của bạn đã được miễn phí vận chuyển") |
| Màu thanh tiến độ | Giữ |
| Vị trí: giỏ mini, trang giỏ, thanh toán, trang sản phẩm | Giữ trang giỏ, thanh toán, trang sản phẩm; giỏ mini khi theme có |
| Chặn mã giảm phí giao khi đã miễn phí theo ngưỡng | **Không cần**: Core chưa có action giảm phí giao ([promotion](../../03-domains/promotion.md)) |
| API cho headless | **Đổi**: dữ liệu tiến độ đi cùng giỏ (`extensions.vani.shipping-flat-rate`) |

## 3. Hành vi

- Số tiền xét ngưỡng **giống hệt** cách tính phí: tiền hàng sau giảm giá của giỏ (`TotalsContext::linesTotal()`), không gồm phí giao.
- `remaining = max(0, free_over − tiền hàng)`, `percent = min(100, floor(tiền hàng / free_over × 100))`, `achieved = remaining = 0`.
- Không hiện khi: chưa cấu hình `free_over`, giỏ trống, hoặc phương thức phí cố định không có hiệu lực (plugin tắt/draining).
- Trang giỏ và thanh toán: cập nhật ngay khi giỏ đổi (dữ liệu lấy từ phản hồi API giỏ, không gọi thêm). Trang sản phẩm: trang có cache, nên số liệu tải sau bằng dữ liệu giỏ của phiên khách (đường lấy phần riêng của khách hiện có — [storefront](../../14-storefront/storefront.md)); không có giỏ thì hiện "Miễn phí giao cho đơn từ …".
- Số tiền định dạng theo `MoneyFormatter`.

## 4. Dữ liệu

Không có bảng; thêm cấu hình vào plugin.

## 5. Cấu hình (thêm vào `vani.shipping-flat-rate`)

| Khoá | Kiểu | Mặc định |
|---|---|---|
| `progress_enabled` | bool | bật |
| `progress_message_pending` | chuỗi, bắt buộc có `{amount}` | như §2 |
| `progress_message_achieved` | chuỗi | như §2 |
| `progress_color` | màu (`#rrggbb`) | màu nhấn của theme |
| `progress_positions` | nhiều lựa chọn: `cart`, `checkout`, `pdp`, `mini_cart` | `cart`, `checkout` |

## 6. Extension point

| Nhu cầu | Extension point | Có sẵn? |
|---|---|---|
| Dữ liệu tiến độ theo giỏ | `StorefrontEnricher` (`cart`) | ✅ |
| Hiển thị giỏ / thanh toán / trang sản phẩm | Slot `vani.storefront.cart.after_lines`, `vani.storefront.checkout.after_shipping`, `vani.storefront.pdp.after_add_to_cart` | ✅ |
| Giỏ mini | Slot trong giỏ mini | **Gap** theme: `vani-base` chưa có giỏ mini → bỏ qua vị trí này đến khi theme có |
| Cấu hình | `settings()` | ✅ |

## 7. Tiêu chí chấp nhận

1. `free_over = 500000`, giỏ 320.000 ₫ → còn thiếu 180.000 ₫, 64%; giỏ 500.000 ₫ → đã đạt, phí giao 0 ở báo giá.
2. Có giảm giá làm tiền hàng tụt dưới ngưỡng → thanh tiến độ và phí giao cùng đổi (một nguồn số liệu).
3. Không cấu hình `free_over` hoặc tắt `progress_enabled` → không có khối, không có khoá trong API giỏ.
4. Thông báo thiếu `{amount}` bị từ chối khi lưu cấu hình.
5. HTML trang sản phẩm (cache) không chứa số tiền giỏ của khách.
