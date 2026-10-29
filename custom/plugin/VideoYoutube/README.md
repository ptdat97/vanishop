# vani.video-youtube

Video sản phẩm từ **YouTube**. Đóng góp `Catalog\Contracts\VideoDriver` (Core 0.3.46), mã driver `youtube`.

- Admin → Catalog → Sản phẩm → **Video**: dán link `youtube.com/watch?v=…`, `youtu.be/…`, `youtube.com/shorts/…`, `/embed/…`, `/live/…` hoặc ID 11 ký tự. Lưu ID video (`videos.reference`).
- Storefront: `embed_url` = `https://www.youtube-nocookie.com/embed/{id}` (không đặt cookie trước khi khách bấm xem), ảnh đại diện `i.ytimg.com/vi/{id}/hqdefault.jpg`.
- Không gọi API YouTube, không cần khoá; không kiểm tra video có tồn tại/công khai.
- Tắt plugin → video YouTube không hiện trên storefront và không thêm mới được; dữ liệu giữ nguyên, bật lại là hiện.

Không có cấu hình, bảng dữ liệu, event.

Phỏng theo driver YouTube của Vanilo (MIT, xem `modules/Catalog/NOTICE`). Khác biệt: nhận cả link (không chỉ ID), trả URL nhúng thay cho HTML iframe.
