# ADR-050 — Product thay Style trong DB, model và API

- Trạng thái: **Accepted** (Owner duyệt 2026-11-26) · Code: **Implemented** (Core 0.5.89, Extension API 1.37.0) · Ngày: 2026-11-26 · Người quyết định: Owner (kế hoạch [db-refactor](../20-roadmap/db-refactor.md) G1)
- Liên quan: [ADR-045](ADR-045-flexible-variant-options.md) (biến thể linh hoạt), [ADR-048](ADR-048-extension-api-version.md) (version API cho plugin), [catalog-pricing §1](../03-domains/catalog-pricing.md#1-mô-hình-sản-phẩm), [database](../07-database/database.md).

## Context

- Tên `styles` có từ mô hình thời trang Style → Màu → Size. Từ ADR-045, sản phẩm có 1–3 trục tuỳ chọn bất kỳ (Dung tích, Chất liệu…), nên "style" không còn đúng với mọi loại hàng.
- Code dùng lẫn hai tên: DB và model là `Style`, tầng ứng dụng, route, UI và Storefront API là `Product` (`ProductService`, `/admin/catalog/products`, `ProductController`). Người mới đọc phải tự ánh xạ hai tên.
- Chưa lên production ([status](../00-overview/status.md#production-readiness)), chưa phát hành Storefront API, Extension API đang ở giai đoạn `dev`.

## Decision

1. Đổi toàn bộ tiền tố `style` → `product`: bảng `styles` → `products`, `style_*` → `product_*`, pivot `category_style`/`collection_style` → `category_product`/`collection_product`, cột `style_id` → `product_id` ở mọi bảng Core và plugin. Giữ nguyên `variants`.
2. Mã sản phẩm: `styles.style_code` → `products.product_code` (unique). Setting `catalog.style_code_prefix|digits` → `catalog.product_code_prefix|digits`.
3. Model `Style` → `Product` và các model, service, enum, factory đi kèm. Biến, request field, prop Vue, kiểu TS, thuộc tính HTML `data-style-*` đổi theo.
4. Sửa thẳng migration gốc rồi `migrate:fresh --seed`; không viết migration đổi tên. Gộp luôn kết quả chuyển đổi của ADR-045 vào migration gốc (`variants` tạo với `option_key`; `order_lines` tạo với `variant_options`; bỏ bảng `colors`, `sizes`, `style_colors`).
5. Storefront API giữ `v1` (chưa phát hành), đổi field `style_*` → `product_*`. Extension API tăng minor 1.37.0 (giai đoạn dev, ADR-048 sửa đổi 2026-11-20); plugin trong repo sửa cùng đợt, tăng version.

## Alternatives

- **Giữ `styles` ở DB, chỉ đổi tên tầng ứng dụng** — đỡ đổi migration nhưng vẫn hai tên, vấn đề chính không được giải quyết.
- **Migration đổi tên (expand → migrate → contract)** — cần khi có dữ liệu thật; hiện không có môi trường nào giữ dữ liệu, nên chỉ thêm việc.
- **Đặt `product_code` cho variant, giữ `style_code`** — trùng nghĩa với `sku`; Owner chọn `product_code` cho sản phẩm.

## Consequences

- Một tên duy nhất từ DB tới UI; tài liệu và test dùng thuật ngữ Product ([glossary](../00-overview/glossary.md)).
- Plugin ngoài repo phải đổi `style_id` → `product_id` trong migration và truy vấn, đổi model/contract theo CHANGELOG-extension 0.5.89.
- Môi trường dev phải `migrate:fresh` (mất dữ liệu demo đã sửa tay, gồm tài khoản Admin).

## Trade-offs

- Một đợt đổi lớn (~340 file) trong một commit thay vì nhiều bước nhỏ; đổi lấy việc không có trạng thái nửa vời. Đợt đổi chỉ đổi tên, không đổi logic; bộ test đầy đủ là lưới an toàn.
