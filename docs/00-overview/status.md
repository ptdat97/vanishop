# Trạng thái triển khai

> Cập nhật lần cuối: **2026-10-02**, sau slice 4 (Inventory). Tài liệu này phải được cập nhật trong mọi PR làm thay đổi trạng thái một capability.

## Thang trạng thái

| Trạng thái | Nghĩa |
|---|---|
| **Implemented** | Code đã có, có test pass, dùng được theo đúng tài liệu |
| **Partially Implemented** | Đã có code nhưng thiếu phần đã thiết kế (ghi rõ thiếu gì) |
| **Designed** | Có thiết kế chi tiết trong `docs/`, chưa có code |
| **Planned** | Mới có ý định hoặc phác thảo, chưa có thiết kế chi tiết |

## Tổng quan

> **Slice 0 (Foundation) đã có code**: khung module, Shared, Tenancy/Brand/Channel tối thiểu, Identity (RBAC theo phạm vi, audit, đăng nhập Admin), Extension (hook + plugin system + CLI), khung Admin Inertia, plugin mẫu, CI. Chưa có giỏ, checkout, đơn.
>
> **Slice 1–4 đã có code**: Catalog, Product, Variant & Price, Inventory (giữ hàng không oversell, đã kiểm chứng bằng concurrency test nhiều tiến trình trên MySQL).
>
> Test: **267 test pass** (unit, feature, architecture) trên cả SQLite in-memory và MySQL, cộng **2 concurrency test** (group `concurrency`, chỉ MySQL).

## Nền tảng

| Hạng mục | Trạng thái | Bằng chứng / Ghi chú |
|---|---|---|
| Laravel 13, PHP 8.4 | Implemented | `laravel/framework 13.33` |
| `modules/` + autoload `Modules\`, `ModuleServiceProvider`, `config/modules.php` | Implemented | `app/Providers/ModuleServiceProvider.php` |
| `custom/plugin/` + autoload `Plugin\`; `custom/theme/` | Implemented (thư mục theme còn trống) | |
| MySQL | Implemented (dev) | `.env` dùng database `vanishop`; mức cô lập `READ COMMITTED` (ADR-016). **Máy dev đang chạy MySQL 9.7.2** (DBngin); CI dùng MySQL 8.4 theo ADR-016 |
| Locale `vi`, `APP_NAME=VaniShop` | Implemented | `.env`, `.env.example` |
| Inertia v3 + Vue 3 + TypeScript 5.9 (Admin) | Implemented | `resources/js/admin.ts`; build + `vue-tsc` pass |
| CI | Implemented (chưa chạy trên GitHub) | `.github/workflows/ci.yml`: clean-room, license, Pint, vue-tsc, build, Pest (SQLite + MySQL 8.4), concurrency group trên MySQL 8.4 |
| Larastan | Planned | Chưa được duyệt dependency |
| Redis, Horizon, Meilisearch | Designed | Queue/cache vẫn dùng driver `database` |

## Theo bounded context

| Context | Trạng thái | Đã có | Còn thiếu |
|---|---|---|---|
| **Shared** | Partially Implemented | `Money`, `Currency`, `MoneyFormatter`, `PhoneNumber`, `CurrentContext`/`ContextScope`/`Actor`, middleware correlation id, `BelongsToBrand` + `BrandScope`, base `ModuleServiceProvider` | Địa giới hành chính VN, `MoneyCast` Eloquent, `idempotency_keys`, `number_sequences` |
| **Tenancy** | Partially Implemented | `legal_entities` + model/factory, contract `LegalEntityDirectory` | Settings kế thừa owner → legal entity → brand → channel |
| **Brand** | Partially Implemented | `brands` (+ `theme_tokens`, `lock_version`), `BrandDirectory`, rule `BrandSlug` (chặn slug trùng đường dẫn dành riêng, ADR-019) | Admin CRUD, preflight |
| **Channel** | Partially Implemented | `channels`, `channel_brands`, `channel_domains`, `ChannelResolver`, `ChannelDirectory` (`forBrand`, `all`), middleware `vani.channel` (`ResolveChannel`, khớp path prefix dài nhất → hỗ trợ brand theo đường dẫn trên domain chung) | Admin CRUD, gán bảng giá/location |
| **Identity** | Partially Implemented | Nhân viên (guard `staff`), đăng nhập/đăng xuất Admin, rate limit, vai trò + permission registry, gán vai trò theo scope owner/legal_entity/brand, `Authorizer`, `Gate::before`, `AuditLogger` (append-only), middleware `vani.staff-context`, lệnh `vani:staff:create-owner`; **ADR-020**: đường dẫn Admin `VANI_ADMIN_PATH`, cookie phiên Admin riêng + idle timeout, IP allowlist (404), `X-Robots-Tag: noindex`, chính sách mật khẩu ≥ 12 ký tự (+ kiểm tra mật khẩu bị lộ ở production), audit đăng nhập từ IP mới | Gửi thông báo khi đăng nhập từ IP mới (hiện chỉ audit + log), scope `location`, Admin quản lý nhân viên/vai trò, SSO. **Không dùng 2FA** (ADR-020) |
| **Extension** | Partially Implemented | `Hook` (filter/action/collect/slot, strict mode, public/internal), `hooks.php` registry, manifest, dependency resolver (semver, conflicts, topo sort), lifecycle install/enable/disable/uninstall (+ `failed`), cache nạp plugin, cô lập plugin lỗi, safe mode, `PluginServiceProvider` API, `AdminNavigation`, CLI `vani:plugin:list/install/enable/disable/uninstall/hooks`, trang Admin "Plugin" | `vani:plugin:upgrade`, `vani:plugin:doctor`, scope `legal_entity`, `settings_schema` → form, circuit breaker, metric `hook_duration_ms` |
| **Catalog** | Partially Implemented (slice 1) | Danh mục theo brand (cây materialized path, tối đa 5 cấp, chống vòng khi di chuyển, `lock_version`, bản dịch vi/en, ảnh), thuộc tính spec/internal + giá trị, màu + `color_family`, size theo hệ size, thư viện media (`media`, `mediables`, khử trùng lặp theo checksum), Admin brand workspace `/{admin}/catalog/{brand}/…`, Storefront API danh mục. **Slice 2:** Style (mã, slug, `draft/active/archived`, khung giờ hiển thị, bản dịch vi/en, danh mục + danh mục chính, thuộc tính theo kiểu nhập, `lock_version`, chỉ xoá bản nháp), Style Color + bộ ảnh theo màu (tối đa 20, đổi thứ tự), bộ sưu tập thủ công, hook `vani.product.before_save`/`after_save`, event `ProductCreated/Updated/Archived` (sau commit), `SearchProvider` (`database` tìm không dấu + facet; `meilisearch` qua REST), listener đồng bộ index (queue `search`), lệnh `vani:search:reindex`. **Slice 3:** Variant (màu × size, SKU `{STYLE}-{COLOR}-{SIZE}` duy nhất toàn hệ thống, barcode duy nhất, active/inactive, khối lượng), sinh ma trận trong Admin, event `VariantCreated`, contract `CatalogReader` + `VariantDirectory` | Bộ sưu tập theo luật, thư viện thuộc tính cấp Owner, resize ảnh qua CDN, import Excel |
| **Pricing** | Implemented (slice 3) | Bảng giá theo brand (`base`/`sale`/`member`, priority, khung giờ, bật/tắt, `lock_version`), gán kênh, giá `bigint` + giá gốc, nhập giá hàng loạt theo mã sản phẩm, `price_history` append-only, audit, event `PriceChanged`, `PricingStrategy` mặc định `price_list_priority` (priority cao thắng → giá thấp hơn; giá base làm giá gốc khi khuyến mãi), contract `PriceResolver` | Giá theo nhóm khách (`member`), giá theo số lượng (`min_qty` > 1), import Excel |
| **Inventory** | Partially Implemented (slice 4) | Location cấp Owner (gán brand + kênh, `stock_authority`, priority, `lock_version`), `stock_levels`, reservation idempotent theo key + TTL + phân bổ theo priority (tách location), release/commit, `stock_movements` append-only, `AvailabilityReader`, `InventoryStrategy` `standard` (strategy chỉ giảm ATS), điều chỉnh tay/kiểm kê/tồn an toàn (chặn location do hệ thống ngoài quản lý), `vani:inventory:release-expired`, event `StockReserved/Released/Committed/Adjusted`, `AvailabilityChanged`; Admin: kho & cửa hàng (Owner), lưới tồn, lịch sử biến động | Transfer, reconciliation, sync từ ERP qua Integration API, import Excel, counter Redis flash sale, tồn theo cửa hàng cho storefront |
| **Storefront** (tầng ghép, ADR-021) | Partially Implemented | `ProductViews` ghép Catalog + Pricing + Inventory (`in_stock`, `available`, `low_stock`); toàn bộ `/api/storefront/v1` | Native storefront (theme Blade) |
| Customer / Cart / Checkout / Promotion / Ordering / Payment / Fulfillment / Returns | Designed | — | Slice 5–9 |
| Content, Notification, Reporting | Planned | Dashboard Admin tối thiểu (slot `vani.admin.dashboard.cards`) nằm ở `app/` | |
| Integration | Designed | — | Slice 11 |
| Storefront native | Designed | Chỉ có middleware `ResolveChannel` | Theme `vani-base` |

## API

| API | Trạng thái |
|---|---|
| `/api/storefront/v1` | Partially Implemented: `GET /categories`, `GET /categories/{slug}`, `GET /products` (q, category, collection, color, attr[], sort, page; facet màu + thuộc tính), `GET /products/{slug}` (PDP, có `variants[].price`, `variants[].available/low_stock`, `price` khoảng giá, `in_stock`; danh sách có `price.min/max/compare_at/discount_percent` + `in_stock`); kênh qua `X-Vani-Channel`, locale qua `X-Vani-Locale`, rate limit 240/phút/IP, định dạng lỗi chuẩn cho `/api/*` |
| `/api/admin/v1`, `/api/integration/v1` | Designed |
| Route Admin (web, Inertia) | Implemented dưới `/{VANI_ADMIN_PATH}` (mặc định `admin`): `/login`, `/logout`, `/plugins`, `/plugins/{slug}/…`, `/catalog/…`, `/pricing/…`, `/inventory/locations`, `/inventory/{brand}/stock`, `/inventory/{brand}/movements` |

## Database (migration đã có)

`legal_entities`, `brands`, `channels`, `channel_brands`, `channel_domains`, `staff_users`, `roles`, `role_permissions`, `staff_role_assignments`, `audit_logs`, `plugins`, `plugin_scopes`, `categories`, `category_translations`, `attributes`, `attribute_translations`, `attribute_values`, `attribute_value_translations`, `colors`, `color_translations`, `sizes`, `media`, `mediables`, `styles`, `style_translations`, `style_colors`, `category_style`, `style_attribute_values`, `collections`, `collection_translations`, `collection_style`, `variants`, `price_lists`, `prices`, `channel_price_lists`, `price_history`, `locations`, `location_brands`, `channel_locations`, `stock_levels`, `stock_reservations`, `stock_movements` (+ bảng mặc định của Laravel). Dữ liệu demo: `php artisan db:seed --class=DemoSeeder`. Các bảng khác trong [database](../07-database/database.md) vẫn ở mức Designed.

## Plugin

| Plugin | Trạng thái |
|---|---|
| `vani.hello-world` (plugin mẫu: slot hook, menu, permission, trang Admin, bật theo scope) | Implemented, có test |
| `vani.vietqr`, `vani.ghn`, `vani.promotion-rules` | Planned (slice 10) |

## Chất lượng

| Hạng mục | Trạng thái |
|---|---|
| Unit + feature test (Foundation → slice 4) | 267 test pass |
| Architecture test (R4, R5, R8, R9, strict types, không dùng hàm debug) | Implemented: `tests/Architecture/ArchitectureTest.php` |
| Concurrency test | Implemented cho reservation: `tests/Concurrency/ReservationConcurrencyTest.php` (12 tiến trình, tồn 5 → đúng 5 thành công; nhiều SKU đảo thứ tự không deadlock) |
| Observability | Partially: có correlation id (header + log context + queued job); chưa có metric/tracing |

## Production readiness

**Chưa sẵn sàng.** Đã có catalog, giá, tồn kho; chưa có giỏ, checkout, đơn, thanh toán, giao hàng. Điều kiện go-live: [roadmap §4](../20-roadmap/roadmap.md).
