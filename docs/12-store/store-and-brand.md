# Cửa hàng và Brand

> Trạng thái: **Designed** (định hướng từ 2026-10-01, [ADR-028](../19-adr/ADR-028-single-store-brand-as-catalog.md)). **Code hiện tại vẫn theo mô hình đa brand cũ** (ADR-008/019); lộ trình chuyển đổi ở §6, tiến độ ở [status](../00-overview/status.md).

## 1. Mô hình

```text
Owner (1 bản cài đặt)
  └── Cửa hàng (store) = 1 pháp nhân vận hành · 1 website · 1 giao diện · 1 bộ cấu hình
        ├── Catalog: Brand ─< Style ─< Style Color ─< Variant
        │            Danh mục · Thuộc tính · Bộ sưu tập (dùng chung cho mọi brand)
        ├── Bảng giá · Khuyến mãi · Voucher            (cấp cửa hàng)
        ├── Kho / cửa hàng vật lý (Location)            (cấp cửa hàng)
        ├── Khách hàng · Giỏ · Đơn · Thanh toán · Vận đơn · Đổi trả
        └── Plugin · Cấu hình · Nhân viên/quyền          (cấp cửa hàng)
```

| Khái niệm | Là gì | Không phải |
|---|---|---|
| **Cửa hàng** | Toàn bộ bản cài đặt: một website, một người bán | Một tenant trong nhiều tenant |
| **Pháp nhân vận hành** | Thông tin người bán (tên công ty, MST, địa chỉ, tài khoản nhận tiền) của cửa hàng; một bản ghi | Chiều phạm vi dữ liệu |
| **Brand** | Nhóm sản phẩm theo thương hiệu, để duyệt, lọc, khuyến mãi, báo cáo | Phạm vi dữ liệu, storefront, giao diện, quyền |
| **Nguồn đơn** | `web`, `app`, `zalo`, `admin`, `pos` — ghi trên đơn để báo cáo | Kênh có catalog/giá riêng |
| **Location** | Kho hoặc cửa hàng vật lý giữ hàng | Brand hay kênh |

## 2. Brand trong Catalog

```text
brands(id, code UNIQUE, slug UNIQUE, name, description NULL, logo_path NULL, meta_title NULL, meta_description NULL,
       status[active|hidden], position, lock_version)
styles.brand_id NULL → brands (ON DELETE RESTRICT)
-- Bản dịch tên/mô tả brand (brand_translations): Planned, thêm khi storefront cần đa ngôn ngữ cho trang brand.
```

| Dùng ở | Cách dùng |
|---|---|
| Storefront | Trang danh sách thương hiệu `/thuong-hieu`, trang brand `/thuong-hieu/{slug}` (sản phẩm của brand, lọc tiếp theo danh mục/màu/size/giá), logo trên PDP, menu |
| Tìm kiếm | Facet `brand` trong `SearchProvider`; tham số `brand` của `GET /api/storefront/v1/products` |
| Khuyến mãi | Rule điều kiện "dòng thuộc brand X" do plugin `vani.promotion-rules` cung cấp (`PromotionContext` mang `brandId` của từng dòng) |
| Đơn hàng | `order_lines` snapshot `brand_id` + `brand_name` (báo cáo không join ngược catalog, R15) |
| Báo cáo | Doanh số, số lượng, tỷ lệ trả theo brand |
| Tích hợp | Payload `vanishop.order.v1` có `brand` trên từng dòng; ERP map brand qua `integration_mappings` |
| Bảng size | Có thể gắn theo brand + loại hàng (thuộc tính catalog) |

Quy tắc: xoá brand bị chặn khi còn style tham chiếu; ẩn brand (`hidden`) thì trang brand trả 404 nhưng sản phẩm vẫn bán. Brand **không** ảnh hưởng giá, tồn, quyền, cấu hình.

## 3. Cấp cửa hàng cho mọi thứ khác

| Dữ liệu | Trước (ADR-008) | Từ ADR-028 |
|---|---|---|
| Danh mục, thuộc tính, màu, size, bộ sưu tập, media | Theo brand | Một bộ cho cả cửa hàng |
| Bảng giá | Theo brand, gán kênh | Cấp cửa hàng (`base`/`sale`/`member`), chọn theo priority |
| Khuyến mãi, voucher | Theo brand | Cấp cửa hàng; giới hạn theo brand bằng rule |
| Giỏ, checkout | Một giỏ theo kênh, một brand mỗi đơn | Một giỏ chứa sản phẩm mọi brand, **một đơn** |
| Số đơn | Tiền tố theo brand | Một dãy cho cửa hàng: `<PREFIX><yymm>-<seq 6 số>`, tiền tố cấu hình (mặc định `VN`) |
| Kho | Gán brand + kênh | Cấp cửa hàng; ATS = Σ ATS các location giao online |
| Khách hàng | Thống kê + consent theo brand | Một hồ sơ, thống kê một cấp; consent theo kênh gửi × mục đích |
| Mẫu thông báo | loại × kênh × brand × locale | loại × kênh gửi × locale |
| Cấu hình (`Settings`) | kênh → brand → pháp nhân → owner | Một cấp: cửa hàng |
| Plugin | Bật theo owner/brand/channel | Bật/tắt toàn cửa hàng |
| Nhân viên | Vai trò theo scope owner/pháp nhân/brand | Vai trò toàn cửa hàng; scope `location` cho nhân viên kho/cửa hàng (Designed) |
| Theme | Theme + tokens theo brand | Một theme đang hoạt động + tokens cấp cửa hàng |
| Integration client | Data scope theo brand | Scope theo tài nguyên/hành động (+ location khi cần) |

## 4. URL

| URL | Nội dung |
|---|---|
| `/` | Trang chủ cửa hàng |
| `/danh-muc/{slug}`, `/san-pham/{slug}` | Danh mục, PDP |
| `/thuong-hieu`, `/thuong-hieu/{slug}` | Danh sách brand, trang brand |
| `/bo-suu-tap/{slug}`, `/tim-kiem` | Bộ sưu tập, tìm kiếm |
| `/gio-hang`, `/thanh-toan` | Giỏ, checkout (một giỏ cho mọi brand) |
| `/tai-khoan/…` | Tài khoản khách |
| `/api/…` | API |
| `/{VANI_ADMIN_PATH}/…` | Admin ([ADR-020](../19-adr/ADR-020-admin-path-no-2fa.md)) |

Slug Việt không dấu; đổi slug tạo redirect 301 (Content `redirects`). Một sitemap (chia file khi lớn), canonical theo URL trên.

## 5. Admin

Admin phẳng, không có brand workspace: `/{admin}/catalog/styles`, `/{admin}/catalog/brands`, `/{admin}/pricing/price-lists`, `/{admin}/orders`… Danh sách sản phẩm, đơn, báo cáo có **bộ lọc brand**. Quyền theo permission (`catalog.edit`, `prices.edit`, `orders.cancel`…).

## 6. Lộ trình chuyển đổi code (slice 12)

Code slice 0–11 cài theo ADR-008/019. Chuyển đổi theo **expand → migrate → contract**, mỗi bước giữ test xanh.

| # | Việc | Module |
|---|---|---|
| 1 ✅ | Tạo `brands` **trong Catalog** (thực thể catalog mới); chuyển dữ liệu từ bảng tenant `brands` cũ; `styles.brand_id` trỏ sang brand catalog | Catalog, Brand |
| 2 ✅ | Thêm `brand_id`, `brand_name` snapshot vào `order_lines`; payload tích hợp mang brand theo dòng | Ordering, Integration |
| 3 ✅ | Bỏ `BelongsToBrand`/`BrandScope` khỏi các model (Catalog, Pricing, Promotion, Ordering, Payment, Fulfillment, Returns, Inventory, Customer, Notification, Integration); bỏ `brand_id` khỏi các bảng đó (khoảng 25 cột) | Toàn Core |
| 4 ✅ | Gộp danh mục/thuộc tính/màu/size/bộ sưu tập về một bộ; xử lý trùng `slug`/`code` giữa các brand cũ | Catalog |
| 5 ✅ | Bảng giá, khuyến mãi, voucher cấp cửa hàng; bỏ `channel_price_lists`; `PriceResolver`/`PromotionContext` không còn tham số kênh/brand phạm vi | Pricing, Promotion |
| 6 ✅ | Checkout: bỏ validator "một brand mỗi đơn"; số đơn một dãy | Checkout, Ordering |
| 7 ✅ | Bỏ module **Channel** (`channels`, `channel_domains`, `channel_brands`, `channel_locations`, `ResolveChannel`, header `X-Vani-Channel`); thêm `orders.source`. Storefront API không còn bắt buộc header kênh | Channel, Storefront, Cart, Inventory, Ordering |
| 8 ✅ | Bỏ module **Brand** tenant (theme tokens chuyển sang cấu hình cửa hàng); `legal_entities` còn một bản ghi "pháp nhân vận hành" trong Tenancy | Brand, Tenancy |
| 9 ✅ | `Settings` một cấp; `CurrentContext` bỏ `brandIds()`/`channel()`; `runAs` chỉ còn actor | Tenancy, Shared |
| 10 ✅ | Identity: vai trò toàn cửa hàng (scope `owner`, sau này `location`); Admin bỏ brand workspace (`loadBrandWorkspaceRoutes`, `vani.admin-brand`) | Identity, mọi Admin |
| 11 ✅ | Extension (**public API, phá vỡ** → Core `0.3.0`, ghi [CHANGELOG-extension](../04-extension/CHANGELOG-extension.md)): plugin bật/tắt toàn cửa hàng, bỏ `plugin_scopes`, `onEvent()` không lọc theo brand, `brandId` trên event thành `@deprecated`; plugin cập nhật `requires.vanishop: ^0.3` | Extension, 6 plugin |
| 12 ✅ | Customer: bỏ `customer_brand_profiles` (thống kê lên `customers`); consent theo kênh gửi × mục đích. Notification: mẫu không theo brand | Customer, Notification |
| 13 ✅ | Integration: bỏ data scope brand của client/webhook | Integration |
| 14 ✅ | Arch test: cấm `brand_id` làm phạm vi ngoài Catalog; xoá test cô lập brand, thêm test trang brand/facet brand | tests |

Done khi: không còn `BelongsToBrand`, `channels`, brand workspace; một giỏ nhiều brand đặt được một đơn; trang `/thuong-hieu/{slug}` và facet brand qua API chạy; toàn bộ test + concurrency test pass.

## 7. Kiểm thử

- Feature: tạo/sửa/ẩn/xoá brand (chặn xoá khi còn style); lọc sản phẩm theo brand qua API; giỏ chứa sản phẩm nhiều brand → một đơn, dòng đơn snapshot đúng brand.
- Feature: khuyến mãi có rule "thuộc brand" chỉ giảm dòng của brand đó.
- Arch: không model nào ngoài Catalog lọc theo `brand_id`.
