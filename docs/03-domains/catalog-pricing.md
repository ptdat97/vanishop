# Catalog & Pricing

> Trạng thái: **Partially Implemented** (slice 1). Context: Catalog, Pricing ([bounded-contexts](../02-architecture/bounded-contexts.md)).
>
> **Định hướng [ADR-028](../19-adr/ADR-028-single-store-brand-as-catalog.md)**: một catalog cho cả cửa hàng; **brand là thực thể của Catalog** (§1.1). Cột "Trạng thái" dưới đây mô tả code hiện tại — code vẫn lọc danh mục/thuộc tính/màu/media/bảng giá theo brand tenant cho tới slice 12.
>
> | Phần | Trạng thái |
> |---|---|
> | Brand là thực thể catalog (`brands`, `brand_translations`, `styles.brand_id`), trang brand, facet brand | Designed (slice 12) |
> | Danh mục theo brand (sẽ thành một cây cho cả cửa hàng): `parent_id` + materialized `path` (`/12/57/`), tối đa **5 cấp**, di chuyển kéo theo cả cây con, chặn chuyển vào cây con của chính nó, không xoá danh mục còn con, `lock_version`, bản dịch vi/en + SEO, ảnh | Implemented |
> | Thuộc tính spec/internal, kiểu `select`/`multiselect`/`text`/`boolean`, giá trị có bản dịch; sửa giá trị giữ nguyên id theo `code` | Implemented |
> | Màu (tên theo brand + `color_family` chuẩn) và size (`size_system` + `sort_order`) | Implemented |
> | Media: `media` (theo brand, khử trùng lặp theo SHA-256) + `mediables` (gắn đa hình theo `role`); disk `VANI_MEDIA_DISK` | Implemented |
> | Style (mã, slug, trạng thái, khung giờ hiển thị, bản dịch, danh mục + danh mục chính, thuộc tính), Style Color + bộ ảnh theo màu, bộ sưu tập thủ công | Implemented (slice 2) |
> | `SearchProvider`: `database` (tìm không dấu qua `styles.search_text`, lọc danh mục gồm danh mục con, màu, thuộc tính; facet) và `meilisearch` (REST, không cần SDK — **plugin `vani.search-meilisearch`** từ 2026-10-14); chọn bằng `VANI_SEARCH_PROVIDER` (provider chưa bật → `database`); `vani:search:reindex [--setup]` (`--setup` cho provider implement `ConfigurableSearchIndex`) | Implemented |
> | Variant/SKU (màu × size, SKU/barcode duy nhất, sinh ma trận), bảng giá gán kênh, `PricingStrategy` `price_list_priority`, `price_history` | Implemented (slice 3) |
> | Giá thành viên theo nhóm khách, giá theo số lượng, import Excel giá | Designed |
> | Bộ sưu tập theo luật, merchandising ghim vị trí trong Admin | Designed |
> | Resize ảnh qua CDN | Designed |

## 1. Mô hình sản phẩm thời trang

Thời trang cần mô hình **Style → Màu → Variant (Màu × Size)** vì ảnh gắn theo màu, tồn kho & giá gắn theo size.

```mermaid
erDiagram
    BRAND |o--o{ STYLE : "thương hiệu"
    STYLE ||--o{ STYLE_COLOR : "có màu"
    STYLE_COLOR ||--o{ VARIANT : "có size"
    STYLE_COLOR ||--o{ MEDIA : "ảnh/video theo màu"
    STYLE }o--o{ CATEGORY : "thuộc"
    STYLE }o--o{ COLLECTION : "thuộc"
    STYLE ||--o{ STYLE_ATTRIBUTE_VALUE : "chất liệu, form..."
    VARIANT ||--o{ PRICE : "theo bảng giá"
    VARIANT ||--o{ STOCK_LEVEL : "theo location"
```

| Thực thể | Định nghĩa | Ví dụ |
|---|---|---|
| **Style** (mẫu) | Sản phẩm mà khách nhìn thấy, 1 trang PDP. Mã `style_code` | `LM24FW-SH012` Áo sơ mi lụa |
| **Style Color** | Biến thể màu của style; có ảnh riêng, có thể có URL riêng (SEO) | `LM24FW-SH012-IVR` Trắng ngà |
| **Variant (SKU)** | Đơn vị bán & tồn kho nhỏ nhất = màu × size. Có `sku`, `barcode` (EAN/GTIN) | `LM24FW-SH012-IVR-M` |
| **Brand** | Thương hiệu của style (tuỳ chọn); có trang riêng, logo, mô tả, SEO | Lumière, Urbanx |
| **Size Chart** | Bảng size theo loại hàng, có thể riêng theo brand | Bảng size áo nữ Lumière |

- Sản phẩm **không có biến thể** (túi, phụ kiện) vẫn có 1 style color + 1 variant (`size = ONE`) để thống nhất luồng.
- **Bundle / set** (set áo + quần): loại style đặc biệt, thành phần là variant; tồn = min(tồn thành phần). Do plugin `vani.product-bundle` cung cấp (P3).
- Trạng thái style: `draft` → `active` → `archived`; lịch hiển thị `published_from/to`.

### 1.1 Brand

Brand là nhóm sản phẩm theo thương hiệu, **không** là phạm vi dữ liệu ([store-and-brand §2](../12-store/store-and-brand.md)):

- `brands(code, slug, status, sort_order, logo)` + `brand_translations(locale, name, description, seo_*)`; `styles.brand_id` (nullable, `RESTRICT`).
- Dùng cho trang `/thuong-hieu/{slug}`, facet tìm kiếm, menu, rule khuyến mãi "thuộc brand", báo cáo, snapshot trên dòng đơn.
- Không ảnh hưởng giá, tồn, quyền, cấu hình. Ẩn brand chỉ ẩn trang brand, không ẩn sản phẩm.

## 2. Thuộc tính

| Loại | Dùng cho | Ví dụ |
|---|---|---|
| **Option axis** | Tạo variant | Màu, Size |
| **Spec attribute** | Hiển thị + lọc | Chất liệu, Form dáng, Cổ áo, Độ dày, Mùa |
| **Internal attribute** | Vận hành, không hiển thị | Nhà cung cấp, Mã ERP, Nhóm thuế |

- **Màu** có 2 tầng: `color` (tên hiển thị, "Trắng ngà") và `color_family` (nhóm lọc chuẩn: Trắng, Đen, Be, Xanh…).
- **Size** có `size_system` (VN, US, EU, số, chữ) và `sort_order` chuẩn (XS < S < M < L < XL…).
- Thuộc tính, màu, size khai báo **một lần cho cả cửa hàng**.

## 3. Danh mục, bộ sưu tập, merchandising

- **Category**: **một cây** cho cả cửa hàng (dùng `parent_id` + đường dẫn materialized `path`), chứa sản phẩm của mọi brand. Duyệt theo brand dùng trang brand + bộ lọc, không dùng cây riêng.
- **Collection**: tập hợp **thủ công** hoặc **theo luật** (ví dụ: `season = FW24 AND tag = best-seller`), dùng cho landing page, campaign.
- **Merchandising**: ghim vị trí sản phẩm, sắp xếp theo doanh số/tồn/mới; ẩn sản phẩm hết size chủ lực.
- **Taxonomy Google/Meta**: ánh xạ category → `google_product_category` để xuất feed quảng cáo.

## 4. Nội dung & media

- Nội dung dịch được (tên, mô tả, hướng dẫn giặt, SEO) lưu `style_translations(locale, ...)`.
- Media lưu trên S3; bảng `media` đa hình (style color, category, banner). Có `alt`, `sort_order`, `role` (`main`, `hover`, `lookbook`, `video`).
- Ảnh phục vụ qua CDN có resize theo tham số (`w`, `h`, `fit`, `format=webp/avif`).
- Khuyến nghị chuẩn ảnh: 4:5, ≥ 1600px cạnh dài.

## 5. Mã hàng & đồng bộ với ERP

- **ERP là nguồn gốc của mã hàng** (`sku`, `barcode`, đơn vị, nhóm hàng, giá vốn) — xem ma trận ở [integration-platform](../11-integration/integration-platform.md).
- **VaniShop là nguồn gốc của nội dung bán hàng** (tên hiển thị, mô tả, ảnh, danh mục, SEO).
- Luồng: ERP tạo item → module Integration nhận → tạo/cập nhật variant ở trạng thái `draft` + gom thành style theo `style_code` → merchandiser bổ sung nội dung → `active`.
- Bảng `external_references(entity_type, entity_id, system, external_id)` lưu ánh xạ mã giữa hệ thống.

## 6. Giá (Pricing)

### 6.1 Mô hình

```
price_lists(id, code, currency_code, type[base|sale|member], customer_group_id NULL, priority, starts_at, ends_at, active)
prices(price_list_id, variant_id, amount, compare_at_amount, min_qty)
```

- **Giá niêm yết** (`compare_at_amount`) và **giá bán** (`amount`). Hiển thị "giảm x%" chỉ khi giá bán < giá niêm yết.
- Giá tính theo **variant**; có thể nhập nhanh theo style/màu (áp cho mọi size).
- Chọn giá (**Implemented**, `Modules\Pricing\Domain\PriceSelection`): lọc bảng giá đang bật, trong khung giờ (code hiện còn lọc theo kênh được gán — bỏ ở slice 12) → bảng giá `priority` cao nhất thắng → cùng priority lấy **giá thấp nhất** → vẫn trùng lấy id nhỏ hơn. Giá gốc: `compare_at` của mức thắng nếu lớn hơn giá bán; nếu mức thắng không phải bảng `base` thì dùng giá `base` cao nhất lớn hơn giá bán. `% giảm` làm tròn **xuống** (không phóng đại mức giảm).
- Tiền theo [money](../02-architecture/money.md).
- Cách chọn giá là extension point:

```php
interface PricingStrategy
{
    public function code(): string;                     // mặc định 'price_list_priority'
    public function resolve(VariantId $variant, PricingContext $ctx): ResolvedPrice;  // amount + compare_at + nguồn bảng giá
}
```

Core dùng strategy được cấu hình cho cửa hàng; giá trả về vẫn là `Money` và được snapshot vào đơn.
- Giá **đã bao gồm VAT** (thói quen B2C tại VN); lưu `tax_class` để tách thuế khi xuất hoá đơn.

### 6.2 Lịch sử giá & tuân thủ

- Ghi `price_history` mỗi lần đổi giá (ai, khi nào, giá cũ/mới) — phục vụ kiểm tra khuyến mãi theo quy định (giá trước khuyến mãi phải là giá thực tế đã bán).
- Giá do ERP đẩy về (nếu ERP làm master giá) hay nhập ở VaniShop là **cấu hình của cửa hàng**.

## 7. Tìm kiếm & lọc

- Một index cho cửa hàng (`products`), document = style (hoặc style color, tuỳ cấu hình).
- Trường tìm: tên (có dấu + không dấu), mã, **brand**, màu, chất liệu, danh mục.
- Facet: danh mục, **brand**, color_family, size **còn hàng**, khoảng giá, chất liệu, bộ sưu tập.
- Đồng bộ index qua event `ProductUpdated`, `PriceChanged`, `AvailabilityChanged` (debounce 5–30 giây).
- Provider tìm kiếm là extension point `SearchProvider` (`index`, `remove`, `search(query, filters, facets)`); mặc định `database`; `meilisearch`, Algolia, Elasticsearch là plugin.
- Từ đồng nghĩa tiếng Việt: "đầm = váy liền", "sơ mi = shirt", "quần bò = jeans".

## 8. Import / Export

- Import Excel theo mẫu (style, variant, giá, thuộc tính), chạy qua queue, có **dry-run** báo lỗi từng dòng.
- Export feed: Google Merchant, Meta Catalog, TikTok Catalog (trường `brand` lấy từ brand catalog).
