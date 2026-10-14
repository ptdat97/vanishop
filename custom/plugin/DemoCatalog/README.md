# vani.demo-catalog

Plugin dữ liệu demo: nhập sản phẩm kèm **ảnh chụp thật** của Owner, đi kèm plugin trong `resources/photos` (lấy từ `VaniCommerce/public/image/catalog/products`, kể cả thư mục con `product-71`). Plugin cũng là implementation tham chiếu cho các service contract nhập dữ liệu của Core: `CatalogImporter`, `PriceImporter`, `StockImporter` (0.3.27).

```bash
php artisan vani:plugin:install vani.demo-catalog && php artisan vani:plugin:enable vani.demo-catalog
php artisan vani:demo:catalog --dry-run        # xem kế hoạch: nhóm ảnh → sản phẩm
php artisan vani:demo:catalog [--limit=10] [--source=/đường/dẫn/ảnh]
```

- **Gom ảnh:** chuẩn hoá tên file (bỏ tiền/hậu tố thời điểm tải lên và chữ "copy"), bỏ ảnh tải lên nhiều lần (giữ bản lớn nhất). Ảnh cùng tiền tố máy chụp (`VNQ`, `DSC`, `DTT`, `IMG`) có số khung cách nhau ≤ 45 được coi là cùng một bộ đồ → một sản phẩm, tối đa 4 ảnh. Bộ ảnh đi kèm: 56 ảnh → 25 sản phẩm.
- **Mỗi sản phẩm:**
  - thương hiệu "Vani Studio", danh mục "Hàng mới về", một màu, size S/M/L;
  - giá niêm yết 390.000–990.000 ₫, tồn đầu kỳ 0–20 tại kho giao online do VaniShop quản lý;
  - mã `VS-<nhóm ảnh>` (vd. `VS-VNQ6565`), tên lấy từ danh sách tên thời trang mẫu.
  - Giá và tồn suy ra từ mã nhóm ảnh, nên chạy lại cho cùng kết quả.
- **Ảnh:** thu nhỏ về cạnh dài ≤ 1600px (`VANI_DEMO_IMAGE_MAX`), JPEG 82, tự xoay theo EXIF. Ảnh đi kèm đã thu sẵn về 1600px (12 MB thay vì 65 MB bản gốc ~8000px), đã xoay và bỏ EXIF. Core lưu qua thư viện media (khử trùng theo checksum, sinh WebP 400/800/1600).
- **Chạy lại an toàn:** sản phẩm đã có chỉ được bổ sung phần còn thiếu, không ghi đè nội dung nhân viên đã sửa. Ảnh chỉ thêm cho màu chưa có ảnh.
- **Kho:** chưa có kho giao online thì sản phẩm và giá vẫn được nhập, tồn bỏ qua (lệnh báo). Tạo kho trong Admin → Tồn kho rồi chạy lại.
- Nguồn ảnh khác: `VANI_DEMO_IMAGES` hoặc `--source` (vd. thư mục bản gốc trong VaniCommerce). Cập nhật bộ ảnh đi kèm: chỉ giữ một bản cho mỗi ảnh (theo luật gom tên ở trên), thu về 1600px trước khi thêm vào repo.
