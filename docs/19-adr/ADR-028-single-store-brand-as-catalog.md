# ADR-028 — Một Owner, một website, một giao diện; brand là thuộc tính catalog

- Trạng thái: Accepted · Ngày: 2026-10-01 · Người quyết định: Owner
- Thay thế: [ADR-008](ADR-008-multi-brand-model.md), [ADR-019](ADR-019-shared-domain-brand-path.md). Sửa đổi một phần: [ADR-009](ADR-009-storefront-architecture.md) (theme theo brand), [ADR-023](ADR-023-stateless-checkout.md) (một brand mỗi đơn), [ADR-025](ADR-025-native-storefront-ssr-slots.md) (override theme brand).
- Tài liệu gốc: [store-and-brand](../12-store/store-and-brand.md).

## Context
ADR-008/019 thiết kế VaniShop như nền tảng **đa thương hiệu**: Owner → pháp nhân → brand → kênh, mỗi brand một storefront theo đường dẫn, dữ liệu (giá, khuyến mãi, danh mục, quyền, cấu hình, plugin) cô lập theo brand. Code slice 0–11 đã làm theo hướng này.

Owner đổi định hướng: **một website bán hàng, một giao diện**, các thương hiệu thời trang là **nhóm sản phẩm trong cùng một cửa hàng** — giống mô hình cửa hàng đơn phổ biến (BeikeShop, OpenCart, WooCommerce…), nơi brand chỉ là một thuộc tính của sản phẩm để duyệt và lọc.

## Problem
Mô hình đa brand tốn kém ở mọi tầng (scope dữ liệu, test cô lập, theme theo brand, Admin theo workspace, plugin bật theo brand, order group cho giỏ nhiều brand), trong khi Owner chỉ cần một cửa hàng. Nó còn đẩy rủi ro pháp lý: một website của nhiều pháp nhân có thể bị xem là sàn TMĐT.

## Decision
1. **Một Owner = một pháp nhân vận hành = một cửa hàng (store).** Một bản cài đặt phục vụ đúng một website trên một domain (`APP_URL`), một bộ thông tin người bán (tên công ty, MST, địa chỉ, tài khoản nhận tiền) dùng cho hoá đơn, chính sách, thông báo Bộ Công Thương.
2. **Một giao diện.** Một theme đang hoạt động (`vani-base` hoặc theme con của nó trong `custom/theme/`), một bộ theme tokens cấp cửa hàng. Không có theme hay tokens theo brand.
3. **Brand là thực thể của Catalog**, không phải phạm vi dữ liệu:
   - `brands` thuộc module **Catalog**: mã, slug, logo, mô tả/SEO đa ngôn ngữ, thứ tự, trạng thái.
   - Mỗi Style có `brand_id` (tuỳ chọn). Brand dùng để: trang thương hiệu `/thuong-hieu/{slug}`, facet lọc và tìm kiếm, menu, điều kiện khuyến mãi (rule plugin), báo cáo doanh số theo brand, snapshot tên brand trên dòng đơn.
4. **Mọi dữ liệu nghiệp vụ ở cấp cửa hàng**: danh mục, thuộc tính, màu/size, bộ sưu tập, bảng giá, khuyến mãi, voucher, kho, đơn hàng, số đơn, mẫu thông báo, cấu hình, plugin. Không có `BelongsToBrand`, không có brand workspace trong Admin.
5. **Không còn "kênh bán" như một chiều cấu hình.** Storefront native và Storefront API (headless, app, Zalo Mini App) dùng **cùng** catalog, giá, tồn, khuyến mãi của cửa hàng. Nguồn đơn (`web`, `app`, `zalo`, `admin`, `pos`, `marketplace`) là **thuộc tính của đơn** để báo cáo, không là phạm vi dữ liệu. Sàn TMĐT/POS là connector plugin.
6. **Phân quyền theo permission**, không theo brand. Scope còn lại: `owner` (toàn cửa hàng) và `location` (nhân viên cửa hàng/kho, Designed).
7. **Plugin bật/tắt toàn cửa hàng.** Cấu hình (`Settings`) một cấp: cửa hàng.
8. Giữ nguyên mọi bất biến thương mại: reservation, state machine, snapshot, `Money`, idempotency, outbox; giữ kiến trúc modular monolith + plugin + extension point.

## Alternatives
- **Giữ đa brand, chỉ cấu hình một brand**: không phải viết lại, nhưng giữ toàn bộ chi phí scope/test/Admin workspace cho một chiều không dùng; người đọc code và tài liệu luôn phải nghĩ về brand.
- **Một website nhưng giá/khuyến mãi theo brand**: lai, phức tạp, không có nhu cầu. Khuyến mãi theo brand vẫn làm được bằng rule điều kiện "thuộc brand".
- **Mỗi brand một bản cài đặt**: mất khách hàng và tồn kho hợp nhất.

## Consequences
- (+) Mô hình đơn giản: bỏ một chiều phạm vi khỏi mọi bảng, query, quyền, test; Admin phẳng; một giỏ chứa sản phẩm nhiều brand, **không cần order group**.
- (+) Pháp lý: một pháp nhân bán hàng của mình trên website của mình → **website TMĐT bán hàng** (thông báo với Bộ Công Thương), không phải sàn.
- (+) SEO dồn về một cấu trúc URL, một sitemap.
- (−) **Code hiện có đang theo mô hình đa brand** (tenant `brands`, `BelongsToBrand`, `channels`, brand workspace, plugin scope theo brand, `brandId` trên event). Cần một slice chuyển đổi ([store-and-brand §6](../12-store/store-and-brand.md)); đổi public API của Extension là thay đổi phá vỡ (Core 0.x → tăng số giữa).
- (−) Nếu sau này Owner muốn một brand có website/giao diện riêng, phải dùng headless (Storefront API) hoặc một bản cài đặt khác.

## Trade-offs
Từ bỏ khả năng tách storefront/giá/quyền theo brand để đổi lấy một hệ thống nhỏ hơn, dễ vận hành và đúng nhu cầu một cửa hàng.

## Clean-room
Mô hình "một cửa hàng, brand là thuộc tính sản phẩm" là khái niệm phổ biến, không thuộc riêng BeikeShop. Thiết kế bảng, tên cột, màn hình và luồng của VaniShop vẫn viết độc lập ([clean-room](../01-principles/clean-room-license.md)); không mở mã BeikeShop để tham khảo cấu trúc `brands`.
