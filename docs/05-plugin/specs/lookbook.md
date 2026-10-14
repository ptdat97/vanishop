# Đặc tả: Lookbook (`vani.lookbook`)

> Trạng thái: **Đặc tả — Owner đã duyệt 2026-10-14** (M0). Nguồn: plugin Lookbook 1.1.1 của VaniCommerce (Owner). Đợt: M1. Đợt M3 (`OutfitBundle`) sẽ thêm gắn sản phẩm lên ảnh.

## 1. Mục tiêu

Trang bộ ảnh thời trang theo mùa/chủ đề: danh sách lookbook và trang chi tiết dạng thư viện ảnh, dẫn khách sang danh mục liên quan.

## 2. Kiểm kê plugin cũ

| Tính năng | Giữ / đổi |
|---|---|
| Lookbook: tên, slug (trống thì tự sinh từ tên), nội dung mô tả, ảnh bìa, nhiều ảnh có thứ tự, bật/tắt, thứ tự hiển thị | Giữ; mô tả dùng Markdown an toàn như `vani.cms` |
| Gắn một danh mục: trang chi tiết có "Xem thêm tại <danh mục>" | Giữ |
| Trang danh sách và trang chi tiết; API danh sách/chi tiết cho headless | Giữ |
| Ảnh tải lên riêng của plugin | **Đổi**: chọn từ Thư viện ảnh dùng chung |
| Cache truy vấn với thời gian cấu hình | **Đổi**: trang công khai đã có cache CDN; plugin chỉ cache trong request |
| Tên chỉ một ngôn ngữ | **Đổi**: tên, mô tả theo ngôn ngữ cửa hàng (bắt buộc ngôn ngữ mặc định) |

## 3. Hành vi

### 3.1 Storefront

- `/lookbook`: lưới lookbook đang bật (ảnh bìa, tên), sắp theo thứ tự rồi mới nhất, phân trang 12.
- `/lookbook/{slug}`: tên, mô tả, ảnh theo thứ tự (xem lớn khi bấm), link "Xem thêm tại …" nếu gắn danh mục còn hiển thị. Lookbook tắt/không tồn tại → 404.
- Trang render không phiên, cache CDN được; có SEO (tiêu đề, mô tả, ảnh chia sẻ = ảnh bìa) và xuất hiện trong sitemap.
- Khối trang chủ "Lookbook": chọn số lượng (1–6), hiện lookbook mới nhất.
- Header có link "Lookbook" khi có ít nhất một lookbook đang bật (theo cấu hình).

### 3.2 Admin

Admin → Nội dung → **Lookbook**: danh sách (ảnh bìa, tên, số ảnh, trạng thái, thứ tự), tìm theo tên/slug, tạo/sửa/xoá. Form: tên/mô tả theo ngôn ngữ, slug, ảnh bìa và các ảnh (chọn nhiều từ Thư viện ảnh, kéo thứ tự, alt mỗi ảnh), danh mục, thứ tự, trạng thái.

## 4. Dữ liệu

| Bảng | Cột chính |
|---|---|
| `plg_lookbooks` | `id`, `slug` (unique), `translations` (JSON: `name`, `description`, `meta_title`, `meta_description` theo locale), `cover_media_id`, `category_id` (null), `position`, `active`, `lock_version`, timestamps |
| `plg_lookbook_images` | `lookbook_id`, `media_id`, `alt` (JSON theo locale), `position` |

Ảnh khai báo sử dụng với Thư viện ảnh. `data.owned` hai bảng, `references` danh mục (danh mục bị xoá → bỏ gắn, không lỗi trang).

## 5. Cấu hình

| Khoá | Kiểu | Mặc định |
|---|---|---|
| `show_in_header` | bool | bật |
| `per_page` | số (6–48) | 12 |

## 6. Extension point

| Nhu cầu | Extension point | Có sẵn? |
|---|---|---|
| Trang `/lookbook`, `/lookbook/{slug}` | `storefrontPages(…, prefix:)` + `storefrontViews` (theme override) | ✅ (mẫu `vani.cms`) |
| Sitemap | `SitemapProvider` | ✅ |
| Link header | Slot `vani.storefront.header.nav` | ✅ |
| Khối trang chủ | `StorefrontBlock` | ✅ |
| API headless | `storefrontRoutes` (`/api/storefront/v1/lookbooks`, `/{slug}`) | ✅ |
| Ảnh | Thư viện ảnh (`MediaDirectory`, chọn ảnh trong Admin) | ✅ |
| Tên/đường dẫn danh mục | `CatalogReader` | ✅ |
| Trang Admin | `adminPages` + `adminMenu` (nhóm `content`) | ✅ |

## 7. Quyền

`lookbook.view`, `lookbook.manage`.

## 8. Tiêu chí chấp nhận

1. Lookbook tắt không hiện ở danh sách, trang chi tiết 404, không có trong sitemap.
2. Slug trống → sinh từ tên (không dấu, gạch ngang); trùng → báo lỗi.
3. Ảnh đang dùng trong lookbook không xoá được ở Thư viện ảnh; xoá lookbook thì bỏ khai báo.
4. Thiếu bản dịch ngôn ngữ khác → hiện ngôn ngữ mặc định.
5. Trang chi tiết không gửi cookie phiên (cache CDN được).
6. Danh mục gắn đã bị ẩn/xoá → không hiện link "Xem thêm".
