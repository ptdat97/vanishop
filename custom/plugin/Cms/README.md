# vani.cms

Plugin nội dung (`kind: content`). Đây là implementation tham chiếu cho `storefrontPages(..., prefix:)` và `SitemapProvider` (Core 0.3.14).

- **Trang** `/trang/{slug}`: giới thiệu, chính sách đổi trả, bảo mật… Mỗi trang có thể hiện link ở menu đầu trang và/hoặc chân trang, kèm thứ tự sắp xếp.
- **Tin tức** `/tin-tuc`, `/tin-tuc/{slug}`: bài viết có ảnh bìa, tóm tắt và phân trang. Menu đầu trang tự có link "Tin tức" khi đã có bài đăng.
- **Soạn thảo** (Admin → Nội dung, quyền `cms.view` / `cms.manage`):
  - Nội dung viết bằng Markdown (GFM). Có xem trước, chèn ảnh từ Thư viện ảnh, và trường SEO (tiêu đề, mô tả).
  - Trạng thái nháp/đăng; có thể hẹn giờ đăng theo giờ Việt Nam.
  - Xem trước bản nháp trên storefront bằng link ký, hết hạn sau 30 phút, có `noindex`.
- **An toàn:** khi render, HTML thô bị bỏ và link `javascript:`/`data:` bị chặn. Người soạn không chèn được script vào storefront.
- **Ảnh** (từ 1.1.0) lấy từ **Thư viện ảnh dùng chung** của Core qua modal chọn ảnh (cần quyền `media.view`; tải ảnh mới trong modal cần `media.manage`):
  - Ảnh bìa lưu `cover_media_id`; ảnh trong bài chèn dạng `![mô tả](media:123)`. URL thật (bản thu nhỏ trong `public/cache`, `srcset`) được tạo lúc hiển thị nên không vỡ khi bật/tắt WebP hay đổi disk. Ảnh đã bị xoá khỏi thư viện thì không hiển thị.
  - Ảnh đang dùng ở trang/bài được khai báo với Core (`MediaDirectory::syncUsages`), nên Thư viện ảnh không cho xoá. Xoá bài, hoặc gỡ plugin có xoá dữ liệu, thì bỏ khai báo này.
  - Bài tạo ở 1.0 (ảnh tải riêng vào thư mục `cms/`, cột `cover_path`) vẫn hiển thị. Chọn ảnh bìa mới từ thư viện sẽ thay ảnh cũ.
  - Nâng từ 1.0: `php artisan vani:plugin:upgrade vani.cms`.
- **Khối page builder** `cms_latest_posts` (bài viết mới) dùng cho trang chủ.
- **Sitemap:** các trang và bài đã đăng được đưa vào `/sitemap.xml`.
- **Link cho menu** (từ 1.3.0, Core 0.3.58): loại link `page` (Trang nội dung) và `article` (Bài viết) cho Admin → Giao diện → Menu. Chỉ nội dung đang công khai mới hiện; nháp/hẹn giờ chưa tới thì mục menu tự ẩn.
- **Chuyên mục tin tức** (từ 1.4.0): Admin → Nội dung → Bài viết → Chuyên mục. Một cấp, URL `/tin-tuc/{chuyên-mục}` dùng chung không gian đường dẫn với bài viết (lưu bị từ chối nếu trùng). Trang tin tức có thanh chuyên mục (chỉ chuyên mục có bài công khai); xoá chuyên mục thì bài của nó thành "không chuyên mục". Link menu `article_category`; chuyên mục vào sitemap.
- **Sản phẩm trong bài** (từ 1.4.0): gắn tối đa 12 mẫu (tìm theo tên/mã, sắp thứ tự), hiện bằng thẻ sản phẩm của theme ở cuối bài; mẫu bị ẩn/ngừng bán tự bỏ khi hiển thị. Nâng từ bản trước: `php artisan vani:plugin:upgrade vani.cms`.
- **Storefront API** (headless): `GET /api/storefront/v1/x/vani-cms/pages/{slug}`, `GET …/categories`, `GET …/posts?per_page=&category=`, `GET …/posts/{slug}` (có `category`, `products`).
- **Dữ liệu:** bảng `plg_cms_pages`, `plg_cms_posts`, `plg_cms_categories`. Tắt plugin thì URL, menu, khối và API đều biến mất; dữ liệu vẫn giữ.

## Quyết định

- **URL cố định** (Owner chốt 2026-10-03): trang ở `/trang/{slug}`, tin tức ở `/tin-tuc` và `/tin-tuc/{slug}`. Không có cấu hình đổi prefix: URL ổn định cho SEO, và route đăng ký lúc boot nên không phụ thuộc cấu hình trong DB (chạy được với `route:cache`).

## Chưa làm

- **Trình soạn thảo WYSIWYG** (hoãn, Owner 2026-10-03): hiện soạn bằng Markdown. Khi làm cần trình soạn thảo trực quan ở Admin (gói npm) và bộ làm sạch HTML theo allowlist ở server (gói PHP), nên phải duyệt dependency theo R25 + ghi ADR. Giữ Markdown làm định dạng lưu, hoặc chuyển sang HTML đã làm sạch: quyết định khi làm.
- Đa ngôn ngữ, thẻ (tag) cho bài viết, lịch sử phiên bản, lượt xem bài.
