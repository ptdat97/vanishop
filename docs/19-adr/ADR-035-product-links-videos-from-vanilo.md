# ADR-035 — Liên kết sản phẩm và video sản phẩm phỏng theo Vanilo (MIT), không phụ thuộc gói Vanilo

- Trạng thái: Accepted · Ngày: 2026-10-29 · Người quyết định: Owner (chọn qua câu hỏi duyệt)
- Liên quan: [ADR-029](ADR-029-commerce-microkernel.md) (Core trung lập, tích hợp nhà cung cấp là plugin), [ADR-032](ADR-032-approved-dependencies-ops-quality.md) (R25 dependency phải duyệt), [clean-room-license §2](../01-principles/clean-room-license.md).

## Context
Owner muốn tận dụng sự trưởng thành của [Vanilo](https://vanilo.io) 6.0 (`vanilo/framework`, MIT) bằng cách đưa các thành phần cần thiết vào `modules/`. Đối chiếu 18 thành phần của Vanilo với VaniShop:

- Đã có bản riêng chặt hơn hoặc không cần với mô hình một cửa hàng: Cart, Checkout, Order, Payment, Shipment, Promotion, Adjustments, Product, MasterProduct, Category, Channel, Properties (trùng Attribute), Taxes (thuế qua `TaxCalculator`), Foundation/Support/Contracts/Translation (keo nối của chính Vanilo).
- Khoảng trống thật: **Links** (liên kết sản phẩm: tương tự, mua kèm, nâng cấp) và **Video** (video sản phẩm trên nền tảng ngoài).

Mọi module Vanilo là module Concord (Proxy, `konekt/enum`, tiền decimal) và kéo theo 17 gói chưa duyệt (Concord, konekt/*, spatie/laravel-medialibrary, eloquent-sluggable, doctrine/dbal…).

## Decision
1. Chỉ lấy **Links** và **Video**, **viết lại theo quy ước `modules/`** (model Eloquent `final`, bảng `*_translations`, migration trong `Persistence/`, service trong `Application/`, contract trong `Contracts/`, audit, Admin Inertia, test Pest). Không chép nguyên file; giữ ghi nhận MIT tại `modules/Catalog/NOTICE`.
2. **Gỡ `vanilo/framework`** khỏi `composer.json` sau khi fork: không thêm dependency nào.
3. **Links** vào Catalog, chỉ cho Style (bỏ đa hình `linkable` của Vanilo — YAGNI): `link_types` (+ bản dịch, bật/tắt, thứ tự), `style_link_groups` (`root_style_id` null = hai chiều, khác null = một chiều), `style_link_group_items`. Khác Vanilo: thêm liên kết vào nhóm đúng chiều của sản phẩm gốc; gỡ chỉ đụng nhóm mà sản phẩm gốc nhìn thấy (sản phẩm thành viên không phá được liên kết một chiều của sản phẩm gốc); bỏ lọc theo Property. Tối đa 24 sản phẩm mỗi loại.
4. **Video**: extension point `Catalog\Contracts\VideoDriver` (kind `video`); Core **không** có driver (theo ADR-029), YouTube là plugin `vani.video-youtube`. Khác Vanilo: driver trả **URL nhúng** thay cho HTML iframe (theme tự dựng iframe, không in HTML do plugin sinh), nhận link lẫn ID; bỏ cột file/stream/kích thước (chưa có upload video). Video dùng chung nhiều sản phẩm (`style_video`), gỡ khỏi sản phẩm cuối thì xoá.
5. PDP (`CatalogReader::productDetail`, Storefront API, theme `vani-base`) có `videos` (driver đang bật) và `links[].products` (thẻ sản phẩm đã lọc hiển thị, dựng bằng `productsByIds` — một truy vấn cho mọi loại).

## Alternatives
- **Giữ `vanilo/framework` làm dependency, dùng trực tiếp**: thêm Concord và 16 gói khác, hai hệ model/enum/tiền song song. Loại.
- **Chép nguyên văn, chỉ đổi namespace**: giữ Concord/Proxy, lệch quy ước dự án. Loại (Owner chọn viết lại).
- **Fork thêm Taxes/Properties**: trùng `TaxCalculator`/Attribute. Để sau nếu cần thuế suất theo nhóm sản phẩm.

## Consequences
- (+) Có liên kết sản phẩm và video mà không thêm dependency; extension point mới được kiểm chứng ngay bằng plugin tham chiếu (R26).
- (+) Dùng lại được các quyết định thiết kế đã được kiểm chứng của Vanilo (nhóm hai chiều/một chiều, registry driver video).
- (−) Không tự nhận bản vá từ Vanilo; khi Vanilo đổi thiết kế phải tự đối chiếu.
- (−) Thêm bảng Catalog và một plugin phải bật để dùng video.
