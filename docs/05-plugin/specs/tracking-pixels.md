# Đặc tả: Theo dõi quảng cáo (`vani.tracking-pixels`)

> Trạng thái: **Đặc tả — Owner đã duyệt 2026-10-14** (M0). Nguồn: plugin PixelTracking 1.0.0 của VaniCommerce (Owner). Đợt: M1. Quy trình: [vanicommerce-plugin-migration](../vanicommerce-plugin-migration.md).

## 1. Mục tiêu

Gửi sự kiện thương mại điện tử chuẩn tới Meta Pixel, Google Analytics 4 và Google Tag Manager để đo quảng cáo, có tuỳ chọn gửi sự kiện **mua hàng** từ server (Meta Conversions API, GA4 Measurement Protocol) để không mất chuyển đổi khi trình duyệt chặn script.

## 2. Kiểm kê plugin cũ

| Tính năng | Giữ / đổi |
|---|---|
| Nhúng script Meta Pixel, GA4, GTM (kèm thẻ `noscript` của GTM) | Giữ |
| Sự kiện trình duyệt: xem trang, xem sản phẩm, xem danh sách (danh mục), tìm kiếm, thêm vào giỏ, bỏ khỏi giỏ, bắt đầu thanh toán, chọn phương thức thanh toán, mua hàng | Giữ |
| Mã sự kiện chung cho sự kiện mua (khử trùng giữa trình duyệt và server) | Giữ |
| Gửi sự kiện mua từ server tới Meta (Conversions API) và GA4 (Measurement Protocol), băm SHA-256 email/SĐT/mã khách | Giữ, chuyển sang chạy nền (queue) |
| Chỉ chạy khi khách đồng ý cookie (biểu thức JS cấu hình được) | Giữ; thêm cách mặc định không cần viết JS (§5) |
| Chế độ debug (log lỗi, endpoint debug GA4) | Giữ |
| Lấy dữ liệu trang bằng cách lọc mảng dữ liệu view của từng controller | **Đổi**: dùng `StorefrontEnricher`/slot của VaniShop |

## 3. Hành vi

### 3.1 Trình duyệt

- Script nhà cung cấp chỉ nạp khi có mã tương ứng và (nếu bật) khách đã đồng ý cookie. Chưa đồng ý: không nạp script, sự kiện xếp hàng trong trang và phát khi khách đồng ý trong cùng trang; tải trang mới mà vẫn chưa đồng ý thì bỏ.
- Mỗi sự kiện gửi đồng thời tới các nền tảng đã cấu hình, mỗi nền tảng dùng tên sự kiện chuẩn của nó.

| Sự kiện | Khi nào | Dữ liệu |
|---|---|---|
| Xem trang | Mọi trang storefront | — |
| Xem sản phẩm | Trang sản phẩm | SKU/variant mặc định, tên, brand, giá, tiền tệ |
| Xem danh sách | Trang danh mục, brand, bộ sưu tập | Tối đa 30 sản phẩm đầu: id, tên, giá, vị trí |
| Tìm kiếm | Trang tìm kiếm | Từ khoá, số kết quả |
| Thêm vào giỏ / bỏ khỏi giỏ | Sau khi API giỏ trả thành công | Variant, số lượng, giá dòng |
| Bắt đầu thanh toán | Mở trang thanh toán | Dòng giỏ, tạm tính |
| Chọn thanh toán | Chọn phương thức thanh toán | Mã phương thức |
| Mua hàng | Trang đơn vừa đặt (lần xem đầu) | Số đơn, tổng, thuế, phí giao, mã giảm giá, dòng đơn, mã sự kiện |

- **Mua hàng chỉ phát một lần mỗi đơn**: đánh dấu đã phát trong trình duyệt theo số đơn; tải lại trang không phát lại.
- Không dùng dữ liệu riêng của khách trong HTML trang có cache CDN (trang công khai render không phiên — [storefront](../../14-storefront/storefront.md)). Dữ liệu sự kiện của trang công khai chỉ gồm dữ liệu sản phẩm; dữ liệu đơn chỉ có ở trang đơn (không cache).

### 3.2 Server (tuỳ chọn)

- Nghe `OrderPlaced`: dựng sự kiện mua (cùng mã sự kiện với trình duyệt, suy ra từ id công khai của đơn), đẩy job gửi Meta và/hoặc GA4. Job có timeout ngắn, thử lại có giới hạn, lỗi chỉ ghi log khi bật debug; không bao giờ ảnh hưởng đặt hàng.
- Dữ liệu định danh gửi đi: email, SĐT (E.164), mã khách — **băm SHA-256** trước khi gửi; IP, user agent, cookie định danh của nền tảng (đọc lúc đặt hàng, lưu trong job, không lưu DB).
- Gửi server-side chỉ khi khách đã đồng ý cookie (ghi nhận cờ đồng ý cùng yêu cầu đặt hàng); không đồng ý thì không gửi.

## 4. Dữ liệu

Không có bảng. Cấu hình ở settings của plugin; secret (token Meta, API secret GA4) lưu dạng secret (mã hoá).

## 5. Cấu hình (Admin → Cấu hình → Theo dõi quảng cáo)

| Khoá | Kiểu | Ghi chú |
|---|---|---|
| `meta_pixel_id` | chuỗi | Trống = tắt Meta |
| `ga4_measurement_id` | chuỗi | `G-…`; trống = tắt GA4 |
| `gtm_container_id` | chuỗi | `GTM-…`; trống = tắt GTM |
| `meta_capi_enabled`, `meta_access_token` (secret), `meta_test_event_code` | bool, chuỗi | Server-side Meta |
| `ga4_mp_enabled`, `ga4_api_secret` (secret) | bool, chuỗi | Server-side GA4 |
| `consent_mode` | chọn: `none` (luôn chạy), `banner` (khối đồng ý cookie của plugin), `custom` (biểu thức JS) | Mặc định `banner` |
| `consent_expression` | chuỗi | Khi `custom` |
| `debug` | bool | Log lỗi gửi, dùng endpoint debug GA4 |

Khi `banner`: plugin hiện thanh đồng ý cookie đơn giản (Đồng ý / Từ chối), lưu lựa chọn ở cookie của trình duyệt 180 ngày. Nội dung thanh lấy từ cấu hình, mặc định tiếng Việt.

## 6. Extension point

| Nhu cầu | Extension point | Có sẵn? |
|---|---|---|
| Nhúng script đầu trang, cuối trang | Slot `vani.storefront.layout.head`, `vani.storefront.layout.body_end` | ✅ |
| Dữ liệu sản phẩm/danh sách/tìm kiếm cho sự kiện | `StorefrontEnricher` (`product`, `product_card`) hoặc điểm tự động view storefront (ADR-031) | ✅ |
| Sự kiện giỏ | JS nghe sự kiện DOM do theme phát sau thao tác giỏ thành công | **Gap**: theme `vani-base` cần phát sự kiện trình duyệt chuẩn (`vani:cart-updated` kèm dòng thay đổi) — thay đổi theme, không phải Core |
| Trang đơn vừa đặt | Slot `vani.storefront.order.after_summary` | ✅ |
| Sự kiện mua phía server | Event `OrderPlaced` qua `onEvent()`; `OrderReader` | ✅ |
| Cờ đồng ý cookie lúc đặt hàng | `CheckoutRequest::$extra` (`vani.order.before_create`) | ✅ |
| Cấu hình | `settings()` (có secret) | ✅ |

## 7. Quyền

Không có màn hình riêng; cấu hình dùng quyền cấu hình hiện có của Admin.

## 8. Tiêu chí chấp nhận

1. Không cấu hình mã nào → không có script nào trong HTML.
2. Có mã Meta + GA4 → trang sản phẩm có script và sự kiện xem sản phẩm đúng giá/tiền tệ; HTML trang công khai không chứa email/SĐT/tên khách.
3. `consent_mode = banner`, chưa đồng ý → không nạp script; bấm Đồng ý → nạp và phát sự kiện đang chờ.
4. Đặt hàng → trang đơn phát sự kiện mua một lần (tải lại không phát lại); khi bật server-side, một job gửi Meta/GA4 với cùng mã sự kiện; dữ liệu định danh đã băm.
5. Meta/GA4 lỗi hoặc timeout → đặt hàng vẫn thành công, job thử lại có giới hạn rồi dừng.
6. Contract/feature test cho enricher và job; không gọi mạng thật trong test (`Http::fake`).

## 9. Khác biệt có chủ đích so với plugin cũ

- Gửi server-side qua **queue**, không chạy cuối request.
- Mặc định có thanh đồng ý cookie (Luật Bảo vệ dữ liệu cá nhân 2025 — [vietnam-localization §6](../../03-domains/vietnam-localization.md)), không bắt Owner tự viết biểu thức JS.
- Không đọc dữ liệu trang bằng cách lọc mảng view của controller.
