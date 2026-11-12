# Biến thể linh hoạt — kế hoạch refactor

> Trạng thái: **In progress** (Owner duyệt 2026-11-12, Core 0.4.24). Quyết định: [ADR-045](../19-adr/ADR-045-flexible-variant-options.md). Mỗi đợt một commit, cập nhật cột "Trạng thái".

## Mô hình đích (Catalog)

| Bảng | Nội dung |
|---|---|
| `option_types` (+ `option_type_translations`) | Loại tuỳ chọn dùng chung: `code`, `display_type` swatch/button/dropdown, `is_filterable`, `position`; tên theo ngôn ngữ |
| `option_values` (+ `option_value_translations`) | Giá trị của loại: `code`, `group` (vd. hệ size), `hex`, `family` (nhóm màu cho lọc), `position`; tên theo ngôn ngữ |
| `style_options` | Trục của sản phẩm: `style_id`, `option_type_id`, `position` (1–3) |
| `style_option_values` | Giá trị dùng cho sản phẩm, thứ tự riêng: `style_id`, `option_value_id`, `position` |
| `variant_option_values` | Tổ hợp của SKU: `variant_id`, `option_type_id`, `option_value_id` (duy nhất theo (variant, type)) |
| `variants.option_key` | Khoá tổ hợp (id giá trị theo thứ tự trục) — duy nhất theo sản phẩm |
| Ảnh | `mediables` gắn với SKU (`role = gallery`) |

Chuyển dữ liệu: `colors` → loại `color` (swatch, lọc được; hex, nhóm màu), `sizes` → loại `size` (button, lọc được; hệ size → `group`), `style_colors` → trục + giá trị của sản phẩm, `variants.style_color_id|size_id` → `variant_option_values`, ảnh của màu → ảnh của từng SKU cùng màu. Bỏ bảng/cột cũ (dev).

## Đợt

| Đợt | Việc | Trạng thái |
|---|---|---|
| **F1 — Dữ liệu Catalog** | Bảng mới + migration chuyển dữ liệu; model; `VariantData`/chỉ mục/sinh SKU theo tổ hợp; Admin danh mục loại tuỳ chọn (thay trang Màu, Size) | |
| **F2 — Đọc ở Catalog/Storefront** | Chi tiết sản phẩm (`options`, ảnh theo SKU), thẻ sản phẩm (swatch của loại swatch), bộ lọc/facet theo loại lọc được (thay nhóm màu, size), tìm kiếm (DB + Meilisearch) | |
| **F3 — Snapshot `options`** | Dòng giỏ, `TotalsLine`, checkout, dòng đơn (bỏ `color_name`, `size_code`), payload tích hợp, phiếu in, file giao hãng, đổi trả, lưới tồn, báo cáo | |
| **F4 — Admin ma trận N trục** | Chọn 1–3 trục + giá trị cho sản phẩm; lưới (2 trục) / danh sách (1, 3 trục); ảnh theo SKU + áp ảnh theo giá trị trục | |
| **F5 — Storefront** | Bộ chọn N trục (swatch/nút/dropdown), khoá giá trị không còn tổ hợp mua được, ảnh theo SKU, không JS vẫn chạy | |
| **F6 — Plugin, tài liệu** | Bảng size, tín hiệu khan hàng, pixel, kho, Meilisearch; OpenAPI, từ điển dữ liệu, CHANGELOG | |
