# Đặc tả: Bảng size và gợi ý size (`vani.size-chart`)

> Trạng thái: **Đặc tả — Owner đã duyệt 2026-10-14** (M0). Nguồn: plugin SizeChart 1.1.0 của VaniCommerce (Owner). Đợt: M1. Thay cho mục `vani.size-advisor` trong [plugin-catalog](../plugin-catalog.md).

## 1. Mục tiêu

Hiện bảng kích thước trên trang sản phẩm và giúp khách chọn size theo chiều cao/cân nặng, để giảm đổi trả vì sai size.

## 2. Kiểm kê plugin cũ

| Tính năng | Giữ / đổi |
|---|---|
| Bảng size có tên đa ngôn ngữ, bật/tắt | Giữ |
| Nội dung gồm nhiều khối theo thứ tự: ảnh, văn bản, bảng (lưới ô) | Giữ; văn bản dùng Markdown an toàn như `vani.cms` |
| Đơn vị gốc của bảng (cm, inch, m) và đơn vị hiển thị; đổi số trong bảng theo tỉ lệ (bỏ hàng/cột tiêu đề, làm tròn 1 chữ số) | Giữ |
| Áp dụng: không gán sản phẩm nào = dùng cho mọi sản phẩm; gán = chỉ các sản phẩm đó; nhiều bảng khớp thì lấy bảng sửa gần nhất | **Đổi**: gán theo sản phẩm, danh mục, thương hiệu, có thứ tự ưu tiên rõ (§3.2) |
| Gợi ý size: bật/tắt; luật gồm khoảng chiều cao (cm), khoảng cân nặng (kg) → size, kèm ghi chú; khách nhập chiều cao/cân nặng, tính ở trình duyệt | Giữ |
| API cho headless: bảng size của sản phẩm | **Đổi**: nằm trong dữ liệu sản phẩm của Storefront API (`extensions.vani.size-chart`) |
| Cache theo sản phẩm, xoá khi sửa bảng | Giữ |

## 3. Hành vi

### 3.1 Storefront

- Trang sản phẩm có link "Bảng size" cạnh phần chọn size khi sản phẩm có bảng áp dụng; bấm mở hộp thoại hiện các khối theo thứ tự. Có nút đổi đơn vị (đơn vị gốc ↔ đơn vị hiển thị) khi bảng cấu hình cả hai.
- Khi bảng bật gợi ý và có luật: hộp thoại có mục "Tìm size của tôi" — nhập chiều cao (cm), cân nặng (kg) → hiện size gợi ý và ghi chú của luật đầu tiên khớp cả hai khoảng; không khớp → "Chưa tìm thấy size phù hợp". Nếu size gợi ý trùng một size của sản phẩm, có nút chọn nhanh size đó.
- Không gửi chiều cao/cân nặng về server, không lưu.

### 3.2 Bảng nào áp dụng cho sản phẩm

Ưu tiên: bảng gán trực tiếp sản phẩm → bảng gán danh mục chính của sản phẩm (rồi danh mục cha, đi lên) → bảng gán thương hiệu → bảng **mặc định** (đánh dấu "mặc định cửa hàng", tối đa một). Chỉ xét bảng đang bật. Mỗi sản phẩm/danh mục/thương hiệu gán tối đa một bảng.

### 3.3 Admin

- Admin → Sản phẩm → **Bảng size**: danh sách (tên, áp dụng cho, trạng thái), tạo/sửa/xoá. Form: tên theo ngôn ngữ cửa hàng, đơn vị gốc/hiển thị, khối nội dung (thêm/xoá/kéo thứ tự; ảnh chọn từ Thư viện ảnh; bảng sửa như lưới, thêm/bớt hàng cột), gợi ý size (bật, danh sách luật), gán danh mục/thương hiệu, đánh dấu mặc định.
- Form sản phẩm có mục "Bảng size": chọn bảng gán riêng cho sản phẩm hoặc "Theo danh mục/thương hiệu".
- Xoá bảng đang gán: hỏi xác nhận; gỡ mọi gán.

## 4. Dữ liệu (bảng của plugin, [ADR-027](../../19-adr/ADR-027-plugin-data-no-core-columns.md))

| Bảng | Cột chính |
|---|---|
| `plg_size_charts` | `id`, `names` (JSON theo locale), `base_unit`, `display_unit` (null = không đổi), `blocks` (JSON danh sách khối: `{type: image, media_id}` / `{type: text, markdown}` / `{type: table, rows: [[…]]}`), `advisor_enabled`, `advisor_rules` (JSON: `size`, `height_min/max`, `weight_min/max`, `note`), `is_default`, `active`, `lock_version`, timestamps |
| `plg_size_chart_assignments` | `chart_id`, `target_type` (`product`/`category`/`brand`), `target_id`; unique (`target_type`, `target_id`) |

- Ảnh khai báo sử dụng với Core (`MediaDirectory::syncUsages`) để Thư viện ảnh không cho xoá ảnh đang dùng.
- Luật gợi ý hợp lệ khi: size không rỗng, bốn giới hạn là số, min ≤ max. Lưu đã chuẩn hoá.
- Manifest khai báo `data.owned` hai bảng; `references` tới sản phẩm/danh mục/thương hiệu (gỡ plugin có purge xoá sạch).

## 5. Cấu hình

Không có cấu hình toàn plugin ngoài bảng mặc định. Đơn vị hỗ trợ: `cm`, `inch`, `m` (tỉ lệ cố định).

## 6. Extension point

| Nhu cầu | Extension point | Có sẵn? |
|---|---|---|
| Trang quản lý bảng size | `adminPages` + `adminMenu` (nhóm `catalog`) | ✅ |
| Chọn bảng trong form sản phẩm | `adminFormSection('product', …)` | ✅ |
| Gán theo danh mục/thương hiệu trong form danh mục/thương hiệu | `adminFormSection('category'|'brand', …)` | **Gap**: Catalog chưa khai báo tài nguyên Admin `category`, `brand` (`AdminScreen::declareResource`, 0.3.39) và form tương ứng chưa render phần mở rộng → tạm thời gán trong form bảng size |
| Link + hộp thoại trên trang sản phẩm | Slot `vani.storefront.pdp.add_to_cart_fields` (hoặc `pdp.after_add_to_cart`) | ✅ |
| Dữ liệu cho headless | `StorefrontEnricher` (`product`) | ✅ |
| Biết danh mục chính, thương hiệu của sản phẩm | `CatalogReader` | ✅ (kiểm tra có trả danh mục chính + chuỗi cha khi viết) |
| Chọn ảnh | Thư viện ảnh (`MediaDirectory`) | ✅ |

## 7. Quyền

`size_chart.view`, `size_chart.manage`.

## 8. Tiêu chí chấp nhận

1. Thứ tự ưu tiên §3.2 đúng cho bốn trường hợp (sản phẩm, danh mục con kế thừa danh mục cha, thương hiệu, mặc định) và khi bảng tắt.
2. Bảng gốc cm, hiển thị inch: số trong ô (trừ hàng/cột đầu) đổi đúng, làm tròn 1 chữ số; ô không phải số giữ nguyên.
3. Luật gợi ý không hợp lệ bị loại khi lưu; gợi ý trả luật đầu tiên khớp.
4. Storefront API sản phẩm có `extensions.vani.size-chart` (khối đã chuẩn hoá, luật); sản phẩm không có bảng → không có khoá.
5. Sửa bảng → trang sản phẩm thấy ngay (xoá cache theo bảng).
6. Text block không chạy được HTML/script (giống `vani.cms`).

## 9. Khác biệt có chủ đích so với plugin cũ

- Gán theo danh mục/thương hiệu và có bảng mặc định, thay cho "không gán = mọi sản phẩm" (dễ áp nhầm khi có nhiều bảng).
- Văn bản dùng Markdown an toàn thay vì HTML tự do.
- Không có route API riêng; dữ liệu đi cùng sản phẩm.
