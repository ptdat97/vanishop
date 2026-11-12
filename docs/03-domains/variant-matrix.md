# Ma trận biến thể (màu × size) — đặc tả

> Trạng thái: **Implemented** (Owner duyệt 2026-11-12) · V1–V2 ✅ 0.4.21, V1b ✅ 0.4.22, V3–V4 ✅ 0.4.23. Khác đặc tả: ảnh vẫn quản lý ở khối "Màu và ảnh" ngay trên ma trận; chọn chưa đủ trục mà bấm thêm giỏ vẫn báo lỗi chung `variant_id` (chưa báo tại nhóm trục); chưa có `Modules\Catalog\Testing\VariantFieldProviderContract` cho plugin (§5).
>
> Nguồn: yêu cầu của Owner (ảnh bộ chọn hiện tại, mẫu JSON options/variants, quy tắc hợp đồng) và một lần nghiên cứu hành vi
> sản phẩm tham khảo theo ngoại lệ clean-room có ghi nhận ([clean-room §7](../01-principles/clean-room-license.md)). Tài liệu
> này chỉ mô tả hành vi bằng lời; người code chỉ đọc tài liệu này.

## 1. Mục tiêu

- Nhân viên cửa hàng tạo và sửa **mọi SKU của một sản phẩm trên một màn hình**: màu × size, mã, trạng thái, giá, tồn — lưu một lần.
- Module khác và plugin **góp cột dữ liệu theo SKU** vào màn hình đó (giá, tồn, trường riêng của plugin) mà Catalog không phụ thuộc chúng.
- Storefront API trả mô hình `options` / `variants` (đã có từ 0.4.22) để mọi frontend dựng bộ chọn; chọn màu thì ảnh đổi theo SKU, size hết hàng bị khoá; plugin công bố được dữ liệu theo SKU cho frontend.

## 2. Mô hình dữ liệu (giữ, không đổi bảng chính)

| Khái niệm | Lưu ở | Ghi chú |
|---|---|---|
| Trục màu | `style_colors` (màu của sản phẩm, có thứ tự) → `colors` (danh mục màu, hex, nhóm) | Giá trị chọn được có id = id màu trong danh mục |
| Trục size | `sizes` (danh mục, hệ size, thứ tự) | Giá trị có id = id size |
| SKU | `variants`: một dòng cho mỗi cặp (màu của sản phẩm, size) — khoá duy nhất cặp này | `status` active/inactive, SKU, barcode, khối lượng |
| Ảnh | Thư viện ảnh **theo màu** của sản phẩm | Thời trang đổi ảnh theo màu, không theo size; SKU trỏ ảnh của màu mình |
| Giá | Pricing: bảng giá `base` theo SKU (và bảng khuyến mãi, nhóm khách) | Không lưu giá trong Catalog |
| Tồn | Inventory: tồn theo kho × SKU | Không lưu tồn trong Catalog |

Khác sản phẩm tham khảo (thiết kế riêng): SKU trỏ giá trị **bằng id**, không bằng chỉ số mảng; trục cố định màu × size của
ngành thời trang (không trục tự do); ảnh ở cấp màu; giá và tồn thuộc module riêng, góp vào qua contract.

## 3. Màn hình Admin (đợt V3)

### 3.1 Bố cục

- Khối "Biến thể" thay hai khối cũ ("Màu và ảnh" + bảng biến thể phẳng). Ảnh của màu vẫn quản lý theo màu (mở từ hàng màu).
- **Lưới**: hàng = màu của sản phẩm (swatch, tên, số ảnh, nút ảnh; kéo để đổi thứ tự), cột = size đã chọn cho sản phẩm (theo thứ
  tự của hệ size). Thêm màu (chọn từ danh mục), thêm/bỏ size (chọn từ danh mục, theo hệ size).
- **Ô** (màu, size) có ba trạng thái: *chưa có* (trống), *đang bán*, *ngừng bán*. Bấm ô để bật/tắt. Ô đang bán hiện tóm tắt (giá,
  tồn) từ các cột góp vào.
- **Bảng chi tiết** dưới lưới: mỗi SKU một dòng (màu, size, SKU, barcode, khối lượng, trạng thái) + **các cột góp vào** (giá niêm
  yết, giá so sánh, tồn kho chính, trường của plugin). Sửa trực tiếp trong ô, không có nút "Sửa" từng dòng.
- **Áp hàng loạt**: chọn màu (hoặc tất cả) và size (hoặc tất cả), nhập giá trị cho một hay nhiều trường (kể cả trường góp vào) →
  áp cho mọi SKU khớp trong bản nháp trên màn hình. Nút nhanh "áp cho cả hàng/cột" ở đầu hàng màu và đầu cột size.
- **SKU tự sinh** khi bật ô: `<mã sản phẩm>-<mã màu>-<mã size>`; sửa được; trùng → báo lỗi ở dòng đó.
- Mọi thay đổi là **bản nháp** trên màn hình; nút **Lưu** gửi một lần; rời trang khi còn thay đổi chưa lưu → hỏi lại.

### 3.2 Lưu (một request, một transaction cho phần Catalog)

- Gửi toàn bộ lưới: thứ tự màu, danh sách ô (màu, size, bật/tắt, trạng thái, SKU, barcode, khối lượng) và giá trị các cột góp vào
  theo SKU (`<khoá nhà cung cấp>.<khoá trường>`).
- Catalog kiểm tra (SKU/barcode đúng dạng, không trùng), rồi áp chênh lệch trong một transaction: tạo ô mới, sửa ô đổi, thứ tự màu.
- **Tắt một ô đã có**: SKU chưa phát sinh gì (không đơn, không giỏ đang mở, không biến động tồn) → **xoá**; đã phát sinh → chuyển
  **ngừng bán** (giữ lịch sử đơn và sổ tồn). "Đã phát sinh" do các module trả lời qua contract (Catalog không đọc bảng của họ).
- Sau khi Catalog lưu xong, từng nhà cung cấp cột lưu phần của mình (giá vào bảng giá, tồn qua kiểm kê) — lỗi của một nhà cung cấp
  báo theo dòng, không làm mất phần Catalog đã lưu. Mọi thao tác ghi audit.
- Quyền: `catalog.manage` cho phần Catalog; mỗi cột góp vào có quyền riêng (vd. giá cần quyền giá, tồn cần quyền điều chỉnh tồn) —
  thiếu quyền thì cột chỉ đọc.

### 3.3 Mở rộng (contract Catalog cho module khác và plugin)

- **Nhà cung cấp cột SKU** (đăng ký qua tag): khoá; danh sách trường (dùng `FieldDefinition` sẵn có: loại, nhãn, bắt buộc, gợi ý);
  quyền cần để sửa; đọc giá trị cho các SKU của sản phẩm; kiểm tra và lưu giá trị theo SKU; tuỳ chọn công bố dữ liệu ra storefront.
- Core tự đóng góp: Pricing (giá niêm yết, giá so sánh của bảng `base`), Inventory (tồn ở kho chính khi cửa hàng một kho; nhiều kho →
  chỉ đọc tổng + link sang lưới tồn).
- **Kiểm tra phát sinh SKU** (đăng ký qua tag): trả các SKU đã có dữ liệu ở module đó (Ordering: dòng đơn; Cart: dòng giỏ đang mở;
  Inventory: biến động/tồn khác 0). Một module trả lời "có" là đủ để chỉ ngừng bán thay vì xoá.
- Nhà cung cấp lỗi (ném lỗi, sai kiểu) được cô lập như mọi extension: cột đó không hiện, phần còn lại vẫn chạy.

## 4. Storefront (đã có từ 0.4.22; bổ sung ở V4)

- API chi tiết sản phẩm: `options` (màu = swatch, size = nút; giá trị có id), `variants` (mọi SKU, `is_active`, `is_default`,
  `option_values` theo id, `price`, `availability`, `media` trỏ ảnh), `media.gallery`, `inventory`, `purchase`.
- **V4**: `variants[].extensions.<khoá nhà cung cấp>` — dữ liệu theo SKU mà nhà cung cấp cột chọn công bố (vd. plugin "mô tả size"
  công bố số đo); mở trang với SKU chọn sẵn qua `?sku=<mã SKU>` (đổi màu, chọn size, đổi ảnh tương ứng); chọn chưa đủ trục mà bấm
  thêm giỏ → báo lỗi ngay tại nhóm trục thiếu.
- Theme mặc định: swatch màu + một hàng size của màu đang chọn (không JS vẫn chạy), chọn màu → ảnh đầu của màu, chọn size → giá của SKU.

## 5. Kiểm chứng

- Feature test: lưu lưới (tạo/sửa/tắt có phát sinh → ngừng bán, không phát sinh → xoá), trùng SKU, quyền theo cột, áp hàng loạt
  không cần (thuần giao diện) nhưng dữ liệu gửi lên được kiểm.
- Contract test cho nhà cung cấp cột (`Modules\Catalog\Testing\VariantFieldProviderContract`) — plugin dùng lại.
- Storefront: `?sku=` chọn sẵn; `extensions` theo SKU có trong schema OpenAPI.
