# Catalog & Pricing

> Trạng thái: **Designed**. Context: Catalog, Pricing ([bounded-contexts](../02-architecture/bounded-contexts.md)).

## 1. Mô hình sản phẩm thời trang

Thời trang cần mô hình **Style → Màu → Variant (Màu × Size)** vì ảnh gắn theo màu, tồn kho & giá gắn theo size.

```mermaid
erDiagram
    BRAND ||--o{ STYLE : "sở hữu"
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
| **Size Chart** | Bảng size theo brand + loại hàng (áo, quần, giày) | Bảng size áo nữ Lumière |

- Sản phẩm **không có biến thể** (túi, phụ kiện) vẫn có 1 style color + 1 variant (`size = ONE`) để thống nhất luồng.
- **Bundle / set** (set áo + quần): loại style đặc biệt, thành phần là variant; tồn = min(tồn thành phần). Do plugin `vani.product-bundle` cung cấp (P3).
- Trạng thái style: `draft` → `active` → `archived`; lịch hiển thị `published_from/to` theo channel.

## 2. Thuộc tính

| Loại | Dùng cho | Ví dụ |
|---|---|---|
| **Option axis** | Tạo variant | Màu, Size |
| **Spec attribute** | Hiển thị + lọc | Chất liệu, Form dáng, Cổ áo, Độ dày, Mùa |
| **Internal attribute** | Vận hành, không hiển thị | Nhà cung cấp, Mã ERP, Nhóm thuế |

- **Màu** có 2 tầng: `color` (tên brand đặt, "Trắng ngà") và `color_family` (nhóm lọc chuẩn: Trắng, Đen, Be, Xanh…) để lọc xuyên brand.
- **Size** có `size_system` (VN, US, EU, số, chữ) và `sort_order` chuẩn (XS < S < M < L < XL…).
- Thuộc tính khai báo theo **brand** nhưng có thể dùng chung từ **thư viện Owner**.

## 3. Danh mục, bộ sưu tập, merchandising

- **Category**: cây phân cấp theo brand (dùng `parent_id` + đường dẫn materialized `path` để truy vấn nhanh). Kênh tập đoàn có cây riêng, ánh xạ tới category brand.
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
price_lists(id, brand_id, code, currency_code, type[base|sale|member], priority, starts_at, ends_at)
prices(price_list_id, variant_id, amount, compare_at_amount, min_qty)
channel_price_lists(channel_id, price_list_id, customer_group_id NULL)
```

- **Giá niêm yết** (`compare_at_amount`) và **giá bán** (`amount`). Hiển thị "giảm x%" chỉ khi giá bán < giá niêm yết.
- Giá tính theo **variant**; có thể nhập nhanh theo style/màu (áp cho mọi size).
- Chọn giá: lọc các bảng giá hợp lệ của channel (theo thời gian, nhóm khách) → lấy theo `priority` → nếu nhiều, lấy **giá thấp nhất** (cấu hình được).
- Tiền theo [money](../02-architecture/money.md).
- Cách chọn giá là extension point:

```php
interface PricingStrategy
{
    public function code(): string;                     // mặc định 'price_list_priority'
    public function resolve(VariantId $variant, PricingContext $ctx): ResolvedPrice;  // amount + compare_at + nguồn bảng giá
}
```

Core chỉ dùng strategy được cấu hình cho channel; giá trả về vẫn là `Money` và được snapshot vào đơn.
- Giá **đã bao gồm VAT** (thói quen B2C tại VN); lưu `tax_class` để tách thuế khi xuất hoá đơn.

### 6.2 Lịch sử giá & tuân thủ

- Ghi `price_history` mỗi lần đổi giá (ai, khi nào, giá cũ/mới) — phục vụ kiểm tra khuyến mãi theo quy định (giá trước khuyến mãi phải là giá thực tế đã bán).
- Giá do ERP đẩy về (nếu ERP làm master giá) hay nhập ở VaniShop là **cấu hình theo brand**.

## 7. Tìm kiếm & lọc

- Index Meilisearch **theo channel** (`products_<channel_code>`), document = style color (hoặc style, tuỳ brand cấu hình).
- Trường tìm: tên (có dấu + không dấu), mã, màu, chất liệu, danh mục.
- Facet: danh mục, color_family, size **còn hàng**, khoảng giá, chất liệu, bộ sưu tập.
- Đồng bộ index qua event `ProductUpdated`, `PriceChanged`, `AvailabilityChanged` (debounce 5–30 giây).
- Provider tìm kiếm là extension point `SearchProvider` (`index`, `remove`, `search(query, filters, facets)`); mặc định `database` (dev) và `meilisearch`; Algolia/Elasticsearch là plugin.
- Từ đồng nghĩa tiếng Việt: "đầm = váy liền", "sơ mi = shirt", "quần bò = jeans".

## 8. Import / Export

- Import Excel theo mẫu (style, variant, giá, thuộc tính), chạy qua queue, có **dry-run** báo lỗi từng dòng.
- Export feed: Google Merchant, Meta Catalog, TikTok Catalog theo channel.
