# vani.cms

Plugin nội dung (`kind: content`). Đây là implementation tham chiếu cho `storefrontPages(..., prefix:)` và `SitemapProvider` (Core 0.3.14).

- **Trang** `/trang/{slug}`: giới thiệu, chính sách đổi trả, bảo mật… Mỗi trang có thể hiện link ở menu đầu trang và/hoặc chân trang, kèm thứ tự sắp xếp.
- **Tin tức** `/tin-tuc`, `/tin-tuc/{slug}`: bài viết có ảnh bìa, tóm tắt và phân trang. Menu đầu trang tự có link "Tin tức" khi đã có bài đăng.
- **Soạn thảo** (Admin → Nội dung, quyền `cms.view` / `cms.manage`):
  - Nội dung viết bằng Markdown (GFM). Có xem trước, chèn ảnh tải lên, và trường SEO (tiêu đề, mô tả).
  - Trạng thái nháp/đăng; có thể hẹn giờ đăng theo giờ Việt Nam.
  - Xem trước bản nháp trên storefront bằng link ký, hết hạn sau 30 phút, có `noindex`.
- **An toàn:** khi render, HTML thô bị bỏ và link `javascript:`/`data:` bị chặn. Người soạn không chèn được script vào storefront.
- **Ảnh** lưu trên disk `vanishop.media.disk`, thư mục `cms/`. Định dạng jpg/png/webp/gif, tối đa 5 MB.
- **Khối page builder** `cms_latest_posts` (bài viết mới) dùng cho trang chủ.
- **Sitemap:** các trang và bài đã đăng được đưa vào `/sitemap.xml`.
- **Storefront API** (headless): `GET /api/storefront/v1/x/vani-cms/pages/{slug}`, `GET …/posts?per_page=`, `GET …/posts/{slug}`.
- **Dữ liệu:** bảng `plg_cms_pages`, `plg_cms_posts`. Tắt plugin thì URL, menu, khối và API đều biến mất; dữ liệu vẫn giữ.

## Quyết định

- **URL cố định** (Owner chốt 2026-10-03): trang ở `/trang/{slug}`, tin tức ở `/tin-tuc` và `/tin-tuc/{slug}`. Không có cấu hình đổi prefix: URL ổn định cho SEO, và route đăng ký lúc boot nên không phụ thuộc cấu hình trong DB (chạy được với `route:cache`).

## Chưa làm

- **Trình soạn thảo WYSIWYG** (hoãn, Owner 2026-10-03): hiện soạn bằng Markdown. Khi làm cần trình soạn thảo trực quan ở Admin (gói npm) và bộ làm sạch HTML theo allowlist ở server (gói PHP), nên phải duyệt dependency theo R25 + ghi ADR. Giữ Markdown làm định dạng lưu, hoặc chuyển sang HTML đã làm sạch: quyết định khi làm.
- Đa ngôn ngữ, chuyên mục/thẻ cho bài viết, menu tuỳ biến (`MenuItemType`), lịch sử phiên bản.
