# Ma trận biến thể — đặc tả

> Trạng thái: **Implemented** (Owner duyệt 2026-11-12) · **Từ 0.4.25 theo [ADR-045](../19-adr/ADR-045-flexible-variant-options.md)**: trục là loại tuỳ chọn dùng chung (1–3 trục, không cố định màu × size), ảnh theo SKU — các mục dưới đã cập nhật theo mô hình này ([kế hoạch F1–F6](../20-roadmap/flexible-variants.md)). Lịch sử: V1–V2 ✅ 0.4.21, V1b ✅ 0.4.22, V3–V4 ✅ 0.4.23. V5 ✅ 0.4.24: contract test `Modules\Catalog\Testing\VariantFieldProviderContract` (cột Core chạy cùng), báo "chọn size" ngay tại nhóm size, số ảnh trong ma trận nối tới khối ảnh của màu. Khác đặc tả: ảnh vẫn tải lên/sắp xếp ở khối "Màu và ảnh" (ma trận hiện ảnh đầu, số ảnh và link tới khối đó).
>
> Nguồn: yêu cầu của Owner (ảnh bộ chọn hiện tại, mẫu JSON options/variants, quy tắc hợp đồng) và một lần nghiên cứu hành vi
> sản phẩm tham khảo theo ngoại lệ clean-room có ghi nhận ([clean-room §7](../01-principles/clean-room-license.md)). Tài liệu
> này chỉ mô tả hành vi bằng lời; người code chỉ đọc tài liệu này.

## 1. Mục tiêu

- Nhân viên cửa hàng tạo và sửa **mọi SKU của một sản phẩm trên một màn hình**: tổ hợp giá trị của 1–3 trục (vd. màu × size), mã, trạng thái, giá, tồn — lưu một lần.
- Module khác và plugin **góp cột dữ liệu theo SKU** vào màn hình đó (giá, tồn, trường riêng của plugin) mà Catalog không phụ thuộc chúng.
- Storefront API trả mô hình `options` / `variants` (đã có từ 0.4.22) để mọi frontend dựng bộ chọn; chọn màu thì ảnh đổi theo SKU, size hết hàng bị khoá; plugin công bố được dữ liệu theo SKU cho frontend.

## 2. Mô hình dữ liệu (ADR-045)

| Khái niệm | Lưu ở | Ghi chú |
|---|---|---|
| Loại tuỳ chọn | `option_types` (+ bản dịch): mã, kiểu hiển thị `swatch`/`button`/`dropdown`, lọc được, thứ tự | Dùng chung cả cửa hàng; Màu (`color`), Size (`size`) có sẵn |
| Giá trị | `option_values` (+ bản dịch): mã, nhóm (vd. hệ size), hex + nhóm màu (loại ô màu), thứ tự | Giá trị chọn được có id = id giá trị |
| Trục của sản phẩm | `style_options` (1–3 loại, có thứ tự) + `style_option_values` (giá trị sản phẩm dùng, thứ tự riêng) | Đã có SKU thì không đổi tập loại; đổi thứ tự trục, thêm/bớt giá trị chưa dùng được |
| SKU | `variants` + `variant_option_values` (một giá trị cho mỗi trục); khoá tổ hợp `option_key` duy nhất trong sản phẩm | `status` active/inactive, SKU, barcode, khối lượng |
| Ảnh | Thư viện ảnh **theo SKU** | Nút "Áp cho mọi SKU cùng <giá trị>" chép bộ ảnh sang các SKU cùng giá trị ở một trục (vd. mọi size cùng màu) |
| Giá | Pricing: bảng giá `base` theo SKU (và bảng khuyến mãi, nhóm khách) | Không lưu giá trong Catalog |
| Tồn | Inventory: tồn theo kho × SKU | Không lưu tồn trong Catalog |

Khác sản phẩm tham khảo (thiết kế riêng): SKU trỏ giá trị **bằng id**, không bằng chỉ số mảng; loại tuỳ chọn là danh mục dùng
chung (không trục tự do theo từng sản phẩm); giá và tồn thuộc module riêng, góp vào qua contract.

## 3. Màn hình Admin (đợt V3)

### 3.1 Bố cục

- Khối "Biến thể": chọn 1–3 trục từ danh mục loại tuỳ chọn (kéo để đổi thứ tự trục), mỗi trục chọn giá trị dùng (kéo để đổi
  thứ tự). Giá trị còn SKU không bỏ được; đã có SKU thì không thêm/bỏ trục.
- **2 trục → lưới**: hàng = giá trị trục 1, cột = giá trị trục 2. **1 hoặc 3 trục → danh sách tổ hợp**. Nút "Bật mọi tổ hợp".
- **Ô** (tổ hợp) có ba trạng thái: *chưa có* (trống), *đang bán*, *ngừng bán*. Bấm ô để bật/tắt. Ô đang bán hiện tóm tắt (giá,
  tồn) từ các cột góp vào.
- **Bảng chi tiết** dưới lưới: mỗi SKU một dòng (màu, size, SKU, barcode, khối lượng, trạng thái) + **các cột góp vào** (giá niêm
  yết, giá so sánh, tồn kho chính, trường của plugin). Sửa trực tiếp trong ô, không có nút "Sửa" từng dòng.
- **Áp hàng loạt**: chọn màu (hoặc tất cả) và size (hoặc tất cả), nhập giá trị cho một hay nhiều trường (kể cả trường góp vào) →
  áp cho mọi SKU khớp trong bản nháp trên màn hình. Nút nhanh "áp cho cả hàng/cột" ở đầu hàng màu và đầu cột size.
- **SKU tự sinh** khi bật ô: `<mã sản phẩm>-<mã giá trị trục 1>-<mã giá trị trục 2>…`; sửa được; trùng → báo lỗi ở dòng đó.
- **Ảnh theo SKU** (SKU đã lưu): cột "Ảnh" của bảng chi tiết mở khối ảnh của SKU — tải lên, chọn từ thư viện, kéo đổi thứ tự, áp bộ ảnh cho mọi SKU cùng giá trị ở một trục.
- Mọi thay đổi là **bản nháp** trên màn hình; **một nút Lưu chung của form sản phẩm** (0.4.30) lưu cả thông tin sản phẩm và ma trận;
  rời trang khi còn thay đổi chưa lưu → hỏi lại.
- **Thêm sản phẩm mới**: khối Biến thể có ngay (sẵn trục Màu + Size, chỉ cần chọn giá trị; trục để trống được bỏ qua — vd. sản phẩm
  chỉ có màu); nhập thông tin + bật SKU rồi Lưu một lần. Ảnh theo SKU, video, liên kết thêm sau lần Lưu đầu.
- **Ít thao tác**: chọn giá trị có sẵn là thêm ngay vào trục và bật sẵn mọi tổ hợp mới; giá trị chưa có thì gõ tên (màu kèm ô chọn
  màu) → tạo và thêm luôn; loại tuỳ chọn mới (vd. Chất liệu) tạo ngay thành trục. Màu tạo nhanh tự nhận nhóm màu từ mã hex để lọc.
- Khối **Thuộc tính** luôn hiện; "+ Thuộc tính mới" tạo thuộc tính (tên, kiểu, các giá trị cách nhau dấu phẩy) ngay tại form.

### 3.2 Lưu (cùng request và transaction với form sản phẩm)

- Gửi toàn bộ lưới: trục (`axes[{option_type_id, value_ids}]` theo thứ tự), danh sách ô (`values` = id giá trị theo thứ tự trục,
  bật/tắt, trạng thái, SKU, barcode, khối lượng) và giá trị các cột góp vào theo SKU (`<khoá nhà cung cấp>.<khoá trường>`).
- Catalog kiểm tra (SKU/barcode đúng dạng, không trùng, giá trị thuộc trục), rồi áp chênh lệch trong một transaction: tắt ô, ghi
  trục và thứ tự giá trị, tạo ô mới, sửa ô đổi.
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

- API chi tiết sản phẩm: `options` (mỗi trục một loại tuỳ chọn, `display_type` theo loại; giá trị có id, `available`), `variants` (mọi SKU, `is_active`, `is_default`,
  `option_values` theo id, `price`, `availability`, `media` trỏ ảnh), `media.gallery`, `inventory`, `purchase`.
- **V4**: `variants[].extensions.<khoá nhà cung cấp>` — dữ liệu theo SKU mà nhà cung cấp cột chọn công bố (vd. plugin "mô tả size"
  công bố số đo); mở trang với SKU chọn sẵn qua `?sku=<mã SKU>` (chọn đúng giá trị các trục, đổi ảnh của SKU); chọn chưa đủ trục mà bấm
  thêm giỏ → báo lỗi ngay tại nhóm trục thiếu.
- Theme mặc định (0.4.28): trục đầu theo kiểu hiển thị (ô màu / nút / danh sách), mỗi giá trị của trục đầu một nhóm SKU nhãn theo
  các trục còn lại (không JS vẫn chạy); chọn → ảnh và giá của SKU. Một trục: chọn thẳng SKU. Form gửi `axis[<mã loại>]`.

## 5. Kiểm chứng

- Feature test: lưu lưới (tạo/sửa/tắt có phát sinh → ngừng bán, không phát sinh → xoá), trùng SKU, quyền theo cột, áp hàng loạt
  không cần (thuần giao diện) nhưng dữ liệu gửi lên được kiểm.
- Contract test cho nhà cung cấp cột (`Modules\Catalog\Testing\VariantFieldProviderContract`) — plugin dùng lại.
- Storefront: `?sku=` chọn sẵn; `extensions` theo SKU có trong schema OpenAPI.
