# vani.catalog-pages

Nội dung Builder riêng cho từng danh mục, thương hiệu, bộ sưu tập — plugin tham chiếu cho `ScopedBuilderSurface` ([storefront](../../../docs/14-storefront/storefront.md)).

- Mở từ nút **Nội dung trang** ở trang sửa danh mục/thương hiệu/bộ sưu tập (cần quyền `storefront.manage`). Trình biên tập, xem trước nháp, nhập/xuất JSON dùng chung của Builder Engine.
- Vùng: **Đầu trang** (`vani.storefront.plp.header` — có block thì thay tên, mô tả, ảnh mặc định của theme; Hero ở đây thành banner có h1, chữ/ảnh để trống thì lấy của trang), **Trên lưới**, **Dưới lưới**, **Khi không có sản phẩm**.
- Vùng có nội dung riêng **thay** nội dung chung của cùng vị trí (Admin → Giao diện → Nội dung trang → Danh sách sản phẩm); vùng để trống vẫn hiện nội dung chung.
- Dữ liệu ở bảng Core `builder_scoped_blocks` (surface `catalog-pages`, phạm vi `category-12`, `brand-3`, `collection-5`). Headless: `GET /api/storefront/v1/pages/layouts/listing?type=…&slug=…` đã gộp nội dung riêng.
- Giới hạn: xoá đối tượng không xoá nội dung riêng của nó (dòng mồ côi, không hiển thị); tắt plugin thì trang dùng lại nội dung chung, bật lại thì nội dung riêng còn nguyên.
