# Đặc tả: Nội dung bổ sung của cửa hàng (bổ sung `vani.cms` 1.2)

> Trạng thái: **Đặc tả — chờ Owner duyệt** (M0, 2026-10-14). Nguồn: plugin ExtraContent 1.2.0 của VaniCommerce (Owner xác nhận có quyền). Đợt: M1. Không tạo plugin mới: phần cần chuyển gộp vào `vani.cms`.

## 1. Mục tiêu

Cho Owner thêm nội dung bán hàng ngoài mô tả sản phẩm: thanh thông báo đầu trang, hộp ưu đãi và điểm nổi bật ở trang sản phẩm, banner đầu trang danh mục.

## 2. Kiểm kê plugin cũ và quyết định

| Tính năng cũ | Quyết định |
|---|---|
| Nội dung giới thiệu thương hiệu phía trên danh sách sản phẩm của thương hiệu | **Đã có**: thương hiệu có `description` trong Catalog. Đối chiếu: nếu cần định dạng (đậm, link, ảnh) → hiển thị `description` bằng Markdown an toàn (thay đổi nhỏ ở theme/Catalog, ngoài đặc tả này) |
| Banner danh mục: nội dung, ảnh nền máy tính/di động, chữ và link nút | **Chuyển** (§3.3) |
| Thanh thông báo xoay vòng đầu trang: nhiều dòng, màu nền/chữ, tốc độ mờ, thời gian dừng | **Chuyển** (§3.1) |
| Hộp ưu đãi ở trang sản phẩm, chọn vị trí (dưới giá, trên/dưới nút mua, dưới khối mua) | **Chuyển** (§3.2) |
| Mô tả bổ sung theo ngôn ngữ cho sản phẩm | **Đã có**: sản phẩm có `description`, `care_instructions` theo ngôn ngữ |
| Ghi chú sản phẩm dạng các ý ngăn bằng dấu `|` | **Chuyển** thành "Điểm nổi bật" (§3.2) |
| Nhãn giảm giá (% hoặc số tiền tiết kiệm) với mẫu chữ | **Đã có** phần %: theme hiện `-x%` khi có giá gốc. Kiểu "tiết kiệm x ₫" là tuỳ chọn của theme, ngoài đặc tả này |
| Ẩn thông tin trên trang sản phẩm (tồn, thương hiệu, SKU, mã, trọng lượng) | **Không chuyển**: storefront VaniShop không hiện số tồn (chỉ "sắp hết"); hiển thị trường nào là việc của theme |
| HTML tự do ở mọi nội dung | **Đổi**: Markdown an toàn (bỏ HTML thô, chặn link `javascript:`) như phần còn lại của `vani.cms` |

## 3. Hành vi

### 3.1 Thanh thông báo

- Một dải trên cùng mọi trang storefront; mỗi dòng là một thông báo Markdown một dòng (cho phép đậm, link). Nhiều dòng thì xoay vòng: hiện mỗi dòng N ms, chuyển mờ M ms; có nút trước/sau; dừng khi rê chuột; tôn trọng "giảm chuyển động" của hệ điều hành (không tự xoay).
- Có thể hẹn khung giờ hiển thị cho cả thanh (từ–đến, giờ cửa hàng).
- Nội dung giống nhau cho mọi khách → nằm trong HTML có cache.

### 3.2 Trang sản phẩm

- **Hộp ưu đãi**: một nội dung Markdown chung cho mọi sản phẩm, vị trí chọn trong cấu hình (dưới giá / dưới nút mua / dưới chi tiết); có thể tắt riêng cho từng sản phẩm.
- **Điểm nổi bật**: danh sách ý ngắn theo ngôn ngữ, nhập ở form sản phẩm (mỗi dòng một ý, tối đa 8), hiện dưới tên sản phẩm. Có trong Storefront API (`extensions.vani.cms.highlights`).

### 3.3 Banner danh mục

- Nhập ở form danh mục: tiêu đề/nội dung Markdown ngắn, ảnh nền máy tính, ảnh nền di động (tuỳ chọn, mặc định dùng ảnh máy tính), chữ nút và link nút (link nội bộ hoặc `https`). Hiện trên đầu trang danh mục, trước bộ lọc.
- Ảnh chọn từ Thư viện ảnh, khai báo sử dụng.

## 4. Dữ liệu

| Bảng | Cột chính |
|---|---|
| `plg_cms_product_extras` | `style_id` (unique), `highlights` (JSON theo locale: danh sách chuỗi), `hide_promo_box` |
| `plg_cms_category_banners` | `category_id` (unique), `content` (JSON theo locale: tiêu đề, Markdown, chữ nút), `button_url`, `desktop_media_id`, `mobile_media_id`, `active` |

Thanh thông báo và hộp ưu đãi lưu ở settings của `vani.cms` (nội dung theo ngôn ngữ). Tăng version `vani.cms` → 1.2.0, migration mới, cập nhật `data.owned`.

## 5. Cấu hình (`vani.cms`)

| Khoá | Kiểu | Mặc định |
|---|---|---|
| `announcement_enabled`, `announcement_lines` (theo ngôn ngữ), `announcement_bg`, `announcement_fg`, `announcement_pause_ms` (1000–15000), `announcement_fade_ms` (0–2000), `announcement_from`, `announcement_to` | | tắt; 3000; 500 |
| `promo_box_enabled`, `promo_box_markdown` (theo ngôn ngữ), `promo_box_position` (`after_price` / `after_add_to_cart` / `after_details`) | | tắt; `after_add_to_cart` |

## 6. Extension point

| Nhu cầu | Extension point | Có sẵn? |
|---|---|---|
| Thanh thông báo trên cùng | Slot ở đầu `<body>`, trước header | **Gap**: chưa có slot, cần thêm `vani.storefront.layout.body_start` (khai báo ở `modules/Storefront/hooks.php` + theme `vani-base`) |
| Hộp ưu đãi / điểm nổi bật | Slot `vani.storefront.pdp.after_price`, `pdp.after_add_to_cart`, `pdp.after_details`, `pdp.after_title`; `StorefrontEnricher` (`product`) | ✅ |
| Nhập điểm nổi bật, tắt hộp ưu đãi theo sản phẩm | `adminFormSection('product', …)` | ✅ |
| Nhập banner ở form danh mục | `adminFormSection('category', …)` | **Gap**: Catalog chưa khai báo tài nguyên Admin `category` và form danh mục chưa render phần mở rộng (cùng gap với `vani.size-chart`) |
| Hiện banner đầu trang danh mục | Slot đầu trang danh sách sản phẩm | **Gap**: chưa có slot trước bộ lọc PLP; cần `vani.storefront.plp.before_listing` (biết danh mục/thương hiệu đang xem) |
| Cấu hình nội dung theo ngôn ngữ | `settings()` | **Kiểm tra khi viết**: settings hiện có nhận giá trị theo ngôn ngữ chưa; nếu chưa, lưu ở bảng riêng của plugin |

## 7. Quyền

Dùng `cms.view` / `cms.manage` hiện có.

## 8. Tiêu chí chấp nhận

1. Thanh thông báo: tắt → không có HTML; hai dòng → xoay vòng; ngoài khung giờ → không hiện; Markdown chứa `<script>` hoặc link `javascript:` bị loại.
2. Hộp ưu đãi đúng vị trí cấu hình; sản phẩm đánh dấu tắt thì không hiện.
3. Điểm nổi bật: tối đa 8 ý, ý rỗng bị bỏ; có trong Storefront API.
4. Banner danh mục: chỉ hiện ở đúng danh mục; thiếu ảnh di động dùng ảnh máy tính; link nút chỉ nhận đường dẫn nội bộ hoặc `https`.
5. Ảnh banner đang dùng không xoá được ở Thư viện ảnh.
