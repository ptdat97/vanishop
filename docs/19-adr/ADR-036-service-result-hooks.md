# ADR-036 — Hook tại kết quả service; chuyển plugin không bắt buộc đặc tả đầy đủ

- Trạng thái: Accepted · Ngày: 2026-11-03 · Người quyết định: Owner
- Sửa: [ADR-031](ADR-031-short-hook-syntax-auto-points.md) (§5), [ADR-034](ADR-034-vanicommerce-plugin-migration.md) (§4), [clean-room-license](../01-principles/clean-room-license.md) (§2, §6). Danh mục: [extension-point-catalog §4.2](../04-extension/extension-point-catalog.md).

## Context
Owner đã mua license BeikeShop nhưng license vẫn hạn chế nhu cầu tuỳ biến (thương hiệu, sở hữu mã). Owner chọn tiếp tục VaniShop là dự án riêng, **viết bằng mã của VaniShop** nhưng theo phong cách mở rộng của BeikeShop để chuyển nhanh ~50 plugin đã viết trên VaniCommerce. Điểm đặc trưng của phong cách đó: Core gọi filter ngay tại kết quả của service (ví dụ kết quả tính tổng đơn), plugin chỉ cần đăng ký listener trong `boot()`.

VaniShop trước đó chỉ có hook khai báo lẻ và điểm tự động ở tầng hiển thị (Admin page, view, API — ADR-031); tầng service (tổng đơn, giá, khuyến mãi, chi tiết đơn) phải qua contract riêng. ADR-034 bắt buộc đặc tả rồi viết lại từng plugin, chậm.

## Decision
1. **Một API helper duy nhất**: `vani_filter()`, `vani_action()`, `vani_add_filter()`, `vani_add_action()` và `vani_add_slot()` (`modules/Extension/helpers.php`), đi qua HookManager: vẫn phải khai báo hook, kiểm kiểu trả về, gắn plugin sở hữu, đo thời gian.
2. **Hook tại kết quả service**: họ `vani.service.<module>.<kết quả>`, Core gọi `vani_filter(...)` ở `return` của service nghiệp vụ. Đợt đầu (0.3.47): `checkout.totals`, `checkout.quote`, `pricing.prices`, `promotion.result`, `catalog.search|product_detail|category_tree`, `ordering.order_detail`, `customer.profile_attributes`, `notification.request`, `fulfillment.shipment_data`. Thêm điểm mới: một dòng `vani_filter` + khai báo trong `hooks.php` của module + catalog + CHANGELOG.
2b. **Domain event thành hook action** (0.3.48): `vani.event.<module>.<event>` cho mọi event Core, chạy sau commit, lỗi listener bị bỏ qua — tương đương `vani_action(...)` sau thao tác ghi, không phải rải lệnh gọi trong từng service.
3. Sửa ADR-031 §5: hook service **được** chạm luồng tiền, nhưng chỉ qua ba điểm `checkout.totals`, `pricing.prices`, `promotion.result`, có quy tắc lỗi `fail`. `checkout.totals` chạy **trước** guard (giảm giá vượt dòng bị cắt), quote và đặt hàng cùng kết quả. Giữ hàng, máy trạng thái đơn, số tiền thu hộ vẫn không mở.
4. Sửa ADR-034 §4: chuyển plugin **không bắt buộc đặc tả đầy đủ**. Mã do Owner viết trong `VaniCommerce/plugins/*` được dùng lại (logic, client API cổng/hãng, dữ liệu); phần gọi lõi cũ viết lại trên hook/contract của VaniShop. Plugin ghi tác giả bên thứ ba (Guangda, akong…) vẫn viết lại từ mô tả hành vi.
5. Clean-room: vẫn không chép mã lõi BeikeShop/VaniCommerce vào VaniShop; CI vẫn chặn `beike`, `guangda`.

## Alternatives
- **Chép mã BeikeShop vào VaniShop** (license cho phép): VaniShop thành sản phẩm phái sinh, phải giữ bản quyền và không đổi thương hiệu — đúng hạn chế Owner muốn tránh. Loại.
- **Lớp giả lập API/schema lõi cũ** (`Vani\Models\*`, bảng `*_descriptions`): tái tạo cấu trúc phái sinh, gánh kiến trúc cũ. Loại.
- **Giữ nguyên chỉ contract**: an toàn nhất nhưng mỗi plugin cần một extension point mới trong Core. Giữ contract cho capability thay thế được; hook service cho sửa kết quả.

## Consequences
- (+) Plugin có thể chỉnh kết quả service bằng `vani_add_filter('vani.service.checkout.totals', …)` thay cho thêm dòng total.
- (+) Thêm điểm mở rộng tầng service rẻ (một dòng Core).
- (−) Plugin sửa kết quả service phụ thuộc hình dạng DTO/mảng: đổi DTO là đổi public API (ghi CHANGELOG).
- (−) Plugin sai ở luồng tiền làm hỏng đặt hàng (`fail`) — cần test của plugin.

## Trade-offs
Mở rộng nhanh và quen tay cho người viết plugin VaniCommerce, đổi lấy bề mặt public API rộng hơn ở tầng service.
