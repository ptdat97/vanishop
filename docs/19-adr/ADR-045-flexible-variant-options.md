# ADR-045 — Biến thể linh hoạt: loại tuỳ chọn dùng chung, 1–3 trục mỗi sản phẩm, ảnh theo SKU

- Trạng thái: **Accepted** · Ngày: 2026-11-12 · Người quyết định: Owner
- Thay thế phần mô hình màu × size cố định của Catalog (bảng `colors`, `sizes`, `style_colors`, cột `variants.style_color_id|size_id`). Bổ sung: [variant-matrix](../03-domains/variant-matrix.md). Kế hoạch: [flexible-variants](../20-roadmap/flexible-variants.md).

## Context

Biến thể được ghi cứng là màu × size ở mọi tầng: Catalog (bảng màu, size, màu của sản phẩm), bộ lọc (nhóm màu, size), dòng giỏ/checkout/đơn (`color_name`, `size_code`), payload ERP, phiếu in, vận đơn, lưới tồn, theme. Sản phẩm không có màu/size (phụ kiện một cỡ, mỹ phẩm theo dung tích, giày theo size + chất liệu) phải giả màu/size.

## Decision (Owner duyệt 4 câu hỏi, 2026-11-12)

1. **Loại tuỳ chọn dùng chung.** Admin quản lý danh mục loại tuỳ chọn (Màu, Size, Chất liệu, Dung tích…): mã, tên theo ngôn ngữ, kiểu hiển thị (`swatch` | `button` | `dropdown`), có lọc được ở danh mục không, thứ tự; mỗi loại có danh sách giá trị (mã, tên theo ngôn ngữ, thứ tự; giá trị của loại swatch có mã màu hex và nhóm màu để lọc). Màu và Size hiện có chuyển thành hai loại có sẵn (Size giữ "hệ size" làm nhóm giá trị).
2. **Mỗi sản phẩm chọn 1–3 trục** từ danh mục và các giá trị dùng cho sản phẩm (có thứ tự riêng). Mỗi SKU là một tổ hợp duy nhất — đúng một giá trị cho mỗi trục. Sản phẩm không có biến thể thật vẫn có ít nhất một trục (vd. "Kích cỡ: Một cỡ").
3. **Ảnh theo từng SKU.** Thư viện ảnh gắn với SKU; Admin có thao tác "áp ảnh cho mọi SKU cùng giá trị <trục>" để không phải tải lại cho từng size. Gallery của sản phẩm là hợp các ảnh SKU (bỏ trùng media).
4. **Snapshot tuỳ chọn ở giỏ, checkout, đơn, payload tích hợp:** danh sách `options: [{type, name, value}]` thay cột/thuộc tính `color_name`, `size_code`. Dev: bỏ cột cũ, chuyển dữ liệu; payload ERP đổi → tăng version, ghi CHANGELOG.
5. **Ma trận Admin tối đa 3 trục:** 2 trục → lưới hàng × cột; 1 hoặc 3 trục → danh sách tổ hợp có lọc theo từng trục; áp hàng loạt theo giá trị trục. Extension point `VariantFieldProvider` / `VariantUsage` giữ nguyên.
6. **API** giữ mô hình `options` / `variants` (0.4.22): `options[]` là các trục của sản phẩm (mã loại, tên, `display_type`, giá trị có id), `variants[].option_values` trỏ (`option` = mã loại, `value_id`), `variants[].media` là ảnh của chính SKU.

## Alternatives

- Trục tự do theo từng sản phẩm: nhập nhanh nhưng không dùng lại giữa sản phẩm, không lọc theo trục ở danh mục — loại.
- Giữ màu × size + một trục phụ: không giải quyết sản phẩm không có màu/size — loại.
- Ảnh theo giá trị một trục "đổi ảnh": ít thao tác hơn nhưng Owner chọn ảnh theo SKU (linh hoạt nhất); bù lại bằng thao tác áp ảnh hàng loạt.

## Consequences

- Bộ lọc danh mục sinh từ các loại tuỳ chọn có cờ "lọc được" (thay bộ lọc màu/size cố định).
- Phá vỡ contract PHP (`VariantData`, `SellableVariant`, `TotalsLine`, `OrderLineData`…), API storefront, payload tích hợp và snapshot dòng đơn — chấp nhận ở dev, ghi CHANGELOG theo đợt.
- Clean-room: thiết kế riêng (id ổn định, danh mục loại dùng chung), không theo cấu trúc trục/chỉ số của sản phẩm tham khảo.
