# vani.media-webp

Ảnh thu nhỏ trong `public/cache` xuất ra **WebP** thay vì giữ định dạng gốc. Đóng góp `Catalog\Contracts\ImageFormat` (Core 0.3.28).

- Ảnh gốc không bị đụng tới; chỉ bản thu nhỏ (`/cache/media/{ab}/{checksum}-w{width}.webp`) đổi định dạng.
- JPEG luôn chuyển; PNG chuyển khi bật **Chuyển cả ảnh PNG** (mặc định bật).
- **Chất lượng**: để trống = `vanishop.media.cache.quality` (80).
- Máy chủ không có encoder WebP (GD thiếu `imagewebp`, Imagick thiếu WEBP) → plugin không nhận ảnh nào, Core giữ định dạng gốc.

Sau khi bật/tắt plugin hoặc đổi cấu hình: `php artisan vani:media:cache --clear` (dọn bản cũ) hoặc `--warm` (tạo trước).

Ý tưởng từ plugin MediaOptimizer của VaniCommerce (module WebP). Khác biệt: không thương lượng theo header `Accept` — URL cố định để web server/CDN trả file tĩnh.
