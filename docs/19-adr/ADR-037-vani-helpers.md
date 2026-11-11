# ADR-037 — Bộ helper ngắn `vani_*` cho plugin và theme

- Trạng thái: Accepted · Ngày: 2026-11-03 · Người quyết định: Owner
- Triển khai: **Implemented** (Core 0.3.59: `vani_route`, `vani_link`; 0.3.69: còn lại + `MediaUrls`, `PluginDirectory`, arch test mục 4, helper trong `public-api.snapshot`). `vani_admin_url` nhận tham số kiểu `mixed` như `route()`; `vani_image` trả chuỗi rỗng khi không có ảnh và chưa cấu hình ảnh thay thế; `vani_asset` trả chuỗi rỗng khi file chưa publish. `PluginDirectory` có thêm `asset()` để `vani_asset` chỉ gọi một contract.
- Liên quan: [ADR-034](ADR-034-vanicommerce-plugin-migration.md), [ADR-036](ADR-036-service-result-hooks.md), [reference-parity-refactor §4](../20-roadmap/reference-parity-refactor.md). Danh mục: [extension-point-catalog](../04-extension/extension-point-catalog.md).

## Context
Plugin trên VaniCommerce và view theme quen gọi hàm toàn cục ngắn để đọc cấu hình, định dạng tiền, lấy URL ảnh, URL Admin, người dùng hiện tại. VaniShop hiện chỉ có helper hook (`vani_filter`, `vani_action`, `vani_add_filter`, `vani_add_action` và bí danh theo ADR-036). Mọi việc khác phải inject contract qua constructor. Cách này đúng trong class, nhưng dài trong view Blade, và mỗi plugin chuyển sang phải viết lại nhiều dòng gọi.

## Problem
Cần cú pháp ngắn cho plugin và view, mà không tạo một đường thứ hai đi vòng contract (logic trùng, truy vấn DB trong view, trạng thái toàn cục), và không tái tạo bộ hàm toàn cục của lõi dẫn xuất BeikeShop (tên, hành vi ngầm như lọc cấu hình khi đọc).

## Decision
1. **Danh sách đóng** helper (thêm helper mới cần sửa ADR này):

   | Helper | Chữ ký | Uỷ quyền cho |
   |---|---|---|
   | `vani_setting` | `(string $namespace, string $key, mixed $default = null): mixed` | `Tenancy\Contracts\Settings::get`. Plugin dùng plugin id làm namespace (như hiện nay) — không có helper riêng cho cấu hình plugin |
   | `vani_money` | `(int $amount): string` | `Shared\Support\MoneyFormatter::formatAmount` với tiền tệ của cửa hàng |
   | `vani_locale` | `(): string` | `CurrentContext::locale()`, rơi về `StoreLocale` |
   | `vani_customer` | `(): ?CustomerData` | Actor khách trong `CurrentContext` → `Customers` (DTO, không trả model) |
   | `vani_staff` | `(): ?Actor` | Actor nhân viên trong `CurrentContext` |
   | `vani_admin_url` | `(string $routeName, array $params = []): string` | `route()` với tiền tố route Admin (`VANI_ADMIN_PATH`) |
   | `vani_image` | `(?int $mediaId, int $width): string` | **Contract mới** `Catalog\Contracts\MediaUrls` (§3); `null` → ảnh placeholder |
   | `vani_link` | `(LinkTarget\|array $target): ?string` | `Storefront\Contracts\LinkResolver` (Implemented 0.3.59); null khi đối tượng đã ẩn/xoá |
   | `vani_route` | `(string $name, mixed $parameters = [], ?string $locale = null, bool $absolute = true): string` | `Shared\Support\LocalizedUrls::route` — URL storefront theo ngôn ngữ ([ADR-038](ADR-038-localized-storefront-urls.md), thêm 2026-11-04, Implemented 0.3.59) |
   | `vani_asset` | `(string $pluginId, string $path): string` | Manifest tài nguyên tĩnh đã publish của plugin (§3.4-2 cùng tài liệu) |
   | `vani_plugin` | `(string $pluginId): ?PluginInfo` | **Contract mới** chỉ đọc `Extension\Contracts\PluginDirectory` (§3): id, tên, phiên bản, đã bật |

2. **Quy tắc viết**: mỗi helper chỉ lấy contract từ container rồi gọi một phương thức; không logic nghiệp vụ, không truy vấn DB trực tiếp, không biến tĩnh. Đặt trong `modules/Extension/helpers.php`; helper gọi contract thương mại đặt ở `helpers.php` của module sở hữu contract (`modules/{Customer,Catalog,Storefront}/helpers.php`, 0.4.1 — vòng 0 không biết thương mại, R29), bọc `function_exists`, `declare(strict_types=1)`, kiểu đầy đủ.
3. **Contract mới** để helper không vi phạm R5/R7 (helper nằm ở Extension, không được gọi `Application` của Catalog):
   - `MediaUrls::url(int $mediaId, ?int $width = null): ?string` và `MediaUrls::urls(array $mediaIds, int $width): array` (batch cho danh sách) — implementation bọc bộ tạo URL ảnh thu nhỏ hiện có của Catalog.
   - `PluginDirectory::find(string $pluginId): ?PluginInfo` — đọc manifest + trạng thái bật, không thao tác lifecycle.
4. **Phạm vi dùng**: helper dành cho `custom/plugin/*`, view theme, view plugin. Class trong `modules/` **không** dùng các helper của ADR này (inject contract) — arch test chặn. Helper hook `vani_*` của ADR-036 không bị quy tắc này ràng buộc (Core gọi `vani_filter` tại kết quả service).
5. **Public API**: các helper ghi vào `public-api.snapshot`, `CHANGELOG-extension`, extension-point-catalog; đổi chữ ký theo compatibility policy.
6. **Không có helper cho**: phản hồi JSON (giữ một định dạng lỗi API), tiền tệ/tỷ giá, kiểm tra môi trường (HTTPS, di động, IP, domain), thao tác tệp, license, debug SQL, kiểm tra mảng. Không có điểm lọc khi đọc cấu hình.

## Alternatives
- **Facade Laravel cho từng contract** (`VaniSettings::get(...)`): cũng ngắn, nhưng thêm một lớp ẩn danh phải giữ đồng bộ chữ ký, và người viết plugin VaniCommerce quen hàm hơn. Loại.
- **Chỉ inject contract, không helper**: an toàn nhất nhưng view dài, chuyển plugin chậm. Loại.
- **Chép bộ hàm toàn cục kiểu cũ** (cấu hình lọc được, registry toàn cục, kiểm tra license): tái tạo cấu trúc phái sinh và hành vi ngầm khó truy vết. Loại.

## Consequences
- (+) View và plugin ngắn: `{{ vani_money($price) }}`, `vani_setting('vani.shipping-flat-rate', 'fee')`.
- (+) Helper test được bằng fake contract; không có đường đi vòng contract.
- (−) Bề mặt public API rộng thêm 10 hàm; đổi chữ ký phải theo compatibility policy.
- (−) Cần hai contract mới (`MediaUrls`, `PluginDirectory`) trước khi có `vani_image`, `vani_plugin`.

## Trade-offs
Tiện như hàm toàn cục cho người viết plugin và theme, đổi lấy một danh sách helper đóng phải quản lý như public API.
