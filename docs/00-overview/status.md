# Trạng thái triển khai

> Cập nhật lần cuối: **2026-09-28**, commit `030298f`. Tài liệu này phải được cập nhật trong mọi PR làm thay đổi trạng thái một capability.

## Thang trạng thái

| Trạng thái | Nghĩa |
|---|---|
| **Implemented** | Code đã merge, có test pass, dùng được theo đúng tài liệu |
| **Partially Implemented** | Đã có code nhưng thiếu phần đã thiết kế (ghi rõ thiếu gì) |
| **Designed** | Có thiết kế chi tiết trong `docs/`, chưa có code |
| **Planned** | Mới có ý định hoặc phác thảo, chưa có thiết kế chi tiết |

## Tổng quan

> **Hiện trạng thực tế: dự án mới là Laravel skeleton.** Toàn bộ kiến trúc VaniShop đang ở mức *Designed*, chưa có module nghiệp vụ nào được code.

| Hạng mục | Trạng thái | Bằng chứng / Ghi chú |
|---|---|---|
| Laravel 13 skeleton, PHP 8.4 | **Implemented** | `composer.json`: `laravel/framework ^13.17` |
| Thư viện hook `tormjens/eventy` | **Implemented** (mới cài package) | Chưa có lớp bọc `Hook` của VaniShop |
| Pest 5, Pint, Laravel Boost | **Implemented** | `tests/` chỉ có `ExampleTest` |
| Tailwind CSS 4 + Vite 8 | **Implemented** | Chưa có theme |
| Tài liệu kiến trúc | **Implemented** | `docs/` |
| Thư mục `modules/` + autoload `Modules\` | **Designed** | Chưa tồn tại |
| Thư mục `custom/plugin/`, `custom/theme/` + autoload `Plugin\` | **Designed** | Chưa tồn tại |
| MySQL 8.4 | **Designed** | `.env` vẫn đang dùng `DB_CONNECTION=sqlite` |
| Locale `vi`, timezone hiển thị `Asia/Ho_Chi_Minh` | **Designed** | `.env` đang là `APP_LOCALE=en`; `config/app.php` timezone `UTC` (đúng thiết kế lưu UTC) |
| Inertia + Vue 3 + TypeScript (Admin) | **Designed** | Chưa cài `inertiajs/inertia-laravel`, `vue` |
| Redis, Horizon, Meilisearch | **Designed** | Queue/cache đang dùng driver `database` |

## Theo bounded context

| Context | Trạng thái | Tài liệu |
|---|---|---|
| Shared (Money, Phone, CurrentContext) | Designed | [money](../02-architecture/money.md), [bounded-contexts](../02-architecture/bounded-contexts.md) |
| Tenancy / Brand / Channel | Designed | [multi-brand](../12-multi-brand/multi-brand.md) |
| Identity & Access | Designed | [security](../15-security/security.md) |
| Extension (hook, plugin loader) | Designed | [extension-model](../04-extension/extension-model.md), [plugin-system](../05-plugin/plugin-system.md) |
| Catalog / Pricing | Designed | [catalog-pricing](../03-domains/catalog-pricing.md) |
| Inventory | Designed | [inventory](../08-inventory/inventory.md) |
| Customer | Designed | [customer](../03-domains/customer.md) |
| Cart / Checkout | Designed | [cart-checkout](../03-domains/cart-checkout.md) |
| Promotion framework | Designed | [promotion](../03-domains/promotion.md) |
| Ordering / Returns | Designed | [order](../09-order/order.md) |
| Payment | Designed | [payment](../10-payment/payment.md) |
| Fulfillment | Designed | [fulfillment](../09-order/fulfillment.md) |
| Content, Notification, Reporting | Planned | Mới mô tả phạm vi trong [bounded-contexts](../02-architecture/bounded-contexts.md) |
| Integration platform | Designed | [integration-platform](../11-integration/integration-platform.md) |
| ERP connector | Designed (abstraction) · vai trò ODO **hoãn** | [erp-integration](../11-integration/erp-integration.md) |
| Storefront native | Designed | [storefront](../14-storefront/storefront.md) |
| Marketplace, Creator/Affiliate (plugin) | Designed (ranh giới) | [marketplace](../13-marketplace/marketplace.md), [creator-affiliate](../13-marketplace/creator-affiliate.md) |

## API

| API | Trạng thái |
|---|---|
| `/api/storefront/v1` | Designed |
| `/api/admin/v1` | Designed |
| `/api/integration/v1` | Designed |

## Database

| Hạng mục | Trạng thái |
|---|---|
| Bảng mặc định Laravel (`users`, `cache`, `jobs`) | Implemented (SQLite) |
| Schema VaniShop ([database](../07-database/database.md)) | Designed, chưa có migration |

## Plugin

| Plugin | Trạng thái |
|---|---|
| Plugin loader, manifest, CLI `vani:plugin:*` | Designed |
| `HelloWorld` (plugin mẫu) | Planned |
| `VietQr`, `Ghn`, `PromotionRules` (3 plugin chứng minh kiến trúc) | Planned |
| Các plugin khác ([plugin-catalog](../05-plugin/plugin-catalog.md)) | Planned |

## Chất lượng

| Hạng mục | Trạng thái |
|---|---|
| Test coverage nghiệp vụ | 0%: chưa có code nghiệp vụ |
| Architecture tests (Pest `arch()`) | Designed ([testing](../17-testing/testing.md)) |
| CI | Planned |
| Observability | Designed ([observability](../16-observability/observability.md)) |

## Production readiness

**Chưa sẵn sàng.** Điều kiện tối thiểu để go-live brand đầu tiên được ghi ở [roadmap](../20-roadmap/roadmap.md), mục "Go-live gate".
