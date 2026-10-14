# Đặc tả: Khối storefront bổ sung (`vani.storefront-extras`)

> Trạng thái: **Đặc tả — chờ Owner duyệt** (M0, 2026-10-14). Nguồn: plugin StorefrontBlocks 1.0.0 và Bestseller 1.0.1 của VaniCommerce (Owner xác nhận có quyền). Đợt: M1. Hai plugin cũ gộp thành một plugin VaniShop.

## 1. Mục tiêu

Thêm khối cho page builder trang chủ và các vị trí gợi ý mua thêm: **Bán chạy**, **Sản phẩm đã xem**, **Video hero**.

## 2. Kiểm kê plugin cũ và quyết định

| Tính năng cũ | Quyết định |
|---|---|
| Khối "Bán chạy nhất" trang chủ, chọn số lượng (mặc định 8, tối đa 50), xếp theo tổng số đã bán từ trước tới nay, cache 5 phút | **Chuyển**, đổi cách tính: theo số lượng đã bán trong N ngày gần nhất (§3.1) |
| Khối "Sản phẩm đã xem" trang chủ (lưới hoặc trượt ngang, 1–20 sản phẩm) | **Chuyển** |
| Thanh "Đã xem" cuối trang sản phẩm (loại sản phẩm đang xem) và cuối trang giỏ (loại sản phẩm đã trong giỏ), bật/tắt và giới hạn riêng (1–20, mặc định 12) | **Chuyển** |
| Lịch sử xem lưu trên server theo khách đăng nhập hoặc phiên | **Đổi**: lưu trong trình duyệt (§3.2) |
| Khối "Video trang chủ": video máy tính và di động, nội dung phủ, nút CTA (chữ + link), tự phát, lặp, tắt tiếng, nút phát/dừng (bắt buộc khi không tự phát) | **Chuyển** |
| Trang "Sản phẩm mới nhất" | **Đã có**: danh sách sản phẩm mặc định sắp mới nhất; khối Core "Lưới sản phẩm" có nguồn "Mới nhất" |

## 3. Hành vi

### 3.1 Khối "Bán chạy"

- Cấu hình khối: tiêu đề, số sản phẩm (1–24), khoảng thời gian (7 / 30 / 90 ngày, mặc định 30).
- Xếp sản phẩm (style) theo **tổng số lượng** trên dòng đơn đã đặt trong khoảng, trừ phần đã huỷ; chỉ lấy sản phẩm còn bán được. Thiếu dữ liệu (cửa hàng mới) → bù bằng sản phẩm mới nhất để khối không trống.
- Kết quả cache 1 giờ (không phụ thuộc khách) — khối nằm trong HTML có cache CDN.

### 3.2 "Sản phẩm đã xem"

- Trang sản phẩm ghi id công khai của sản phẩm vào danh sách trong `localStorage` của trình duyệt (mới nhất trước, bỏ trùng, tối đa 30). Không gửi về server, không lưu DB, không cần đăng nhập; xoá dữ liệu trình duyệt là mất.
- Khối/thanh "Đã xem" render khung rỗng trong HTML (cache được), JS lấy danh sách id rồi gọi Storefront API để lấy thẻ sản phẩm (chỉ sản phẩm còn bán), loại sản phẩm đang xem (trang sản phẩm) hoặc đã trong giỏ (trang giỏ), cắt theo giới hạn. Danh sách rỗng → ẩn khối.
- Kiểu hiển thị: lưới hoặc trượt ngang.

### 3.3 Khối "Video hero"

- Cấu hình: URL video máy tính, URL video di động (tuỳ chọn), ảnh poster (Thư viện ảnh), tiêu đề/nội dung phủ (Markdown ngắn) và bật/tắt phủ, chữ + link nút, tự phát (luôn kèm tắt tiếng — trình duyệt chặn tự phát có tiếng), lặp, nút phát/dừng (bắt buộc khi không tự phát).
- URL video phải là `https` và đuôi `.mp4`/`.webm` (lưu trên S3/CDN của cửa hàng); Thư viện ảnh hiện chỉ nhận ảnh.
- Tôn trọng "giảm chuyển động": không tự phát, hiện poster + nút phát. Video tải lười, có `poster` để không làm chậm LCP.

## 4. Dữ liệu

Không có bảng. Cấu hình khối lưu trong page builder của Core; cấu hình vị trí "Đã xem" ở settings.

## 5. Cấu hình plugin

| Khoá | Kiểu | Mặc định |
|---|---|---|
| `recent_on_product`, `recent_on_product_limit` (1–20) | bool, số | bật, 12 |
| `recent_on_cart`, `recent_on_cart_limit` (1–20) | bool, số | bật, 12 |

## 6. Extension point

| Nhu cầu | Extension point | Có sẵn? |
|---|---|---|
| Ba khối page builder | `StorefrontBlock` | ✅ |
| **Thẻ sản phẩm theo danh sách id** cho khối và cho API "đã xem" | Service contract trả thẻ sản phẩm (giá, ảnh, nhãn) giống lưới Core | **Gap Core**: `ProductViews` là nội bộ Storefront; cần contract public (vd. `Storefront\Contracts\ProductCards::forIds(list<string> $publicIds, locale)`) — dùng chung cho `vani.lookbook` M3 và khối khác |
| Lấy thẻ sản phẩm cho JS "đã xem" | Storefront API | **Gap Core**: `GET /api/storefront/v1/products` chưa lọc theo danh sách id; thêm tham số `ids[]` (tối đa 30) |
| Top sản phẩm theo số lượng bán | `OrderStatistics` | **Gap Core**: `breakdown(Product)` gom theo **tên** sản phẩm; cần phương thức trả id style theo số lượng (vd. `topStyles(from, to, limit)`), dựa trên `order_lines.style_id`, trừ dòng huỷ |
| Thanh "Đã xem" trang sản phẩm / giỏ | Slot `vani.storefront.pdp.after_details`, `vani.storefront.cart.after_lines` | ✅ |
| Poster | Thư viện ảnh | ✅ |

## 7. Quyền

Không có màn hình riêng; khối cấu hình trong page builder (quyền hiện có), settings trong Admin → Cấu hình.

## 8. Tiêu chí chấp nhận

1. "Bán chạy": đơn huỷ và dòng huỷ không được tính; sản phẩm ngừng bán không xuất hiện; cửa hàng chưa có đơn → khối hiện sản phẩm mới nhất.
2. "Đã xem": HTML trang (cache) không chứa sản phẩm đã xem của ai; JS hiện đúng thứ tự mới nhất, bỏ sản phẩm đang xem / đã trong giỏ, ẩn khi rỗng.
3. API `ids[]` chỉ trả sản phẩm còn bán, giữ thứ tự id truyền vào, tối đa 30.
4. "Video hero": URL không phải `https` hoặc không phải mp4/webm bị từ chối khi lưu; không tự phát → có nút phát; giảm chuyển động → không tự phát.
5. Contract test cho ba `StorefrontBlock`.

## 9. Khác biệt có chủ đích so với plugin cũ

- Bán chạy theo cửa sổ thời gian thay vì cộng dồn từ trước tới nay (sản phẩm cũ bán nhiều không chiếm chỗ mãi).
- Lịch sử xem trong trình duyệt: không lưu dữ liệu duyệt web của khách trên server (ít dữ liệu cá nhân hơn), không phá cache CDN.
