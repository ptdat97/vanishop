# Bản đồ hệ thống (tham chiếu mức mã nguồn)

> Trạng thái: **Partially Implemented**, mô tả **code đang có**; bề mặt nào chưa có code ghi rõ `Designed`. Kiểm chứng từ `bootstrap/app.php`, `config/modules.php`, `modules/Shared/Support/ModuleServiceProvider.php`, `modules/*/*ServiceProvider.php`. Bổ sung cho [overview](overview.md) (vì sao chọn kiến trúc này) và [bounded-contexts](bounded-contexts.md) (ranh giới nghiệp vụ).

> **Một cửa hàng ([ADR-028](../19-adr/ADR-028-single-store-brand-as-catalog.md))**: code hiện có một cửa hàng và không có module Brand/Channel, brand workspace hay header `X-Vani-Channel`; brand là thực thể Catalog.

## 1. Năm bề mặt, một lõi

Mọi bề mặt gọi **cùng Application layer / Contracts** của module. Không bề mặt nào tự tính giá, tồn, khuyến mãi hay trạng thái đơn.

| Bề mặt | Đối tượng | Prefix | Auth | Nạp bởi | Trạng thái |
|---|---|---|---|---|---|
| **Storefront native** (Blade SSR) | Khách trên web | `/…` (một website, [store-and-brand §4](../12-store/store-and-brand.md)) | Phiên web (khách) | `StorefrontServiceProvider`, `storefront-web.php`, theme `custom/theme/vani-base` | Partially Implemented ([storefront](../14-storefront/storefront.md)) |
| **Storefront API** (JSON) | Headless, mobile, Zalo Mini App | `/api/storefront/v1` | Khách vãng lai: token giỏ/đơn ([ADR-022](../19-adr/ADR-022-guest-cart-token.md)); đã đăng nhập: Bearer ([ADR-024](../19-adr/ADR-024-customer-api-token.md)) | `loadStorefrontApiRoutes()` (Storefront, Customer) | Implemented |
| **Admin** (Inertia + Vue) | Nhân viên | `/{VANI_ADMIN_PATH}` ([ADR-020](../19-adr/ADR-020-admin-path-no-2fa.md)) | Guard `staff`, phiên Admin riêng | Module providers đăng ký `loadAdminRoutes()` / `loadAdminSectionRoutes()` | Implemented |
| **Admin API** (JSON) | App quản trị, công cụ nội bộ | `/api/admin/v1` | Designed | — | Designed |
| **Integration API** (JSON) | ERP, POS, đối tác | `/api/integration/v1` | Client key + chữ ký HMAC, scope và allowlist IP | `IntegrationServiceProvider` | Partially Implemented; ERP connector đang đóng băng |

Ngoài ra có hai cửa vào **từ hệ thống ngoài** (không phải bề mặt cho người dùng): callback cổng thanh toán `/api/payments/{gateway}/callback` và webhook hãng vận chuyển `/api/shipping/{carrier}/webhook`. Cả hai là route **chung**; plugin chỉ cung cấp phần xác minh + chuẩn hoá (`verifyCallback`, `parseWebhook`), Core ghi nhận. Webhook riêng của plugin nằm dưới `/api/integrations/{slug}/…`.

> Nguyên tắc bắt nguồn từ hệ tham chiếu ([reference-comparison L1](reference-comparison.md)): **thêm một bề mặt = thêm lớp trình bày mỏng**, không thêm logic. Ví dụ: Storefront API và storefront native dùng cùng `ProductViews`, `CartPresenter`, `CheckoutPresenter` của module `Storefront` ([ADR-021](../19-adr/ADR-021-storefront-composition-module.md)).

## 2. Ba namespace

| Namespace | Thư mục | Vai trò | Được sửa khi thêm capability? |
|---|---|---|---|
| `App\` | `app/` | Khung Laravel: provider nạp module, middleware Inertia, dashboard tối thiểu, logging | Hạn chế, không chứa nghiệp vụ |
| `Modules\` | `modules/` | **Commerce Kernel**: 18 module | Chỉ khi thêm **extension point tổng quát** (R1) |
| `Plugin\` | `custom/plugin/` | Capability nghiệp vụ | Có |

Theme storefront hiện tại là `custom/theme/vani-base`.

## 3. Module và thứ tự nạp

`App\Providers\ModuleServiceProvider` đăng ký provider của từng module theo **đúng thứ tự** trong `config/modules.php` (upstream trước):

```text
Shared → Tenancy → Identity → Extension
→ Catalog → Pricing → Inventory → Cart → Promotion → Ordering → Customer
→ Payment → Checkout → Fulfillment → Returns → Notification → Integration → Storefront
```

Vòng theo [commerce-kernel §1](commerce-kernel.md): Shared, Tenancy, Identity, Extension = **vòng 0** (microkernel); module thương mại = **vòng 1**; plugin = vòng 2–3. Brand là model của Catalog, không phải module tenancy.

| Nhóm | Module | Vai trò |
|---|---|---|
| Vòng 0 — nền tảng | Shared, Tenancy, Identity | Money, context, settings, nhân viên + RBAC + audit |
| Vòng 0 — microkernel | **Extension** | Hook registry, plugin loader, lifecycle, kích hoạt theo scope, registry implementation (`Extensions`) |
| Vòng 1 — thương mại | Catalog, Pricing, Inventory, Cart, Promotion, Ordering, Customer, Payment, Checkout, Fulfillment, Returns | Primitives + invariants |
| Vòng 1 — khung dùng chung | Notification, Integration | Extension point + primitive cho nhiều plugin |
| Vòng 1 — tầng ghép | Storefront | Ghép dữ liệu từ contract của nhiều module cho bề mặt khách, không có bảng |

`Extension` nạp plugin **trong `register()`** (`PluginLoader::load()`), nên provider của plugin được boot **cùng vòng** với module Core, sau khi mọi module đã đăng ký binding. Chi tiết: [request-lifecycle §1](request-lifecycle.md).

## 4. Cấu trúc bên trong một module

```text
modules/<Ctx>/
├── <Ctx>ServiceProvider.php   # extends Modules\Shared\Support\ModuleServiceProvider
├── Contracts/                 # PUBLIC: interface + Data (DTO) + exception nghiệp vụ
├── Events/                    # PUBLIC: domain event (dispatch sau commit)
├── hooks.php                  # PUBLIC: hook do module công bố (registry)
├── Domain/                    # quy tắc thuần PHP (R8)
├── Application/               # use case, service, listener, job
├── Persistence/               # Eloquent model, migration
├── Http/                      # controller, request, resource, routes/*.php
├── Testing/                   # bộ contract test cho extension contract của module
├── resources/{js,lang}/
└── Tests/
```

Plugin chỉ được dùng `Contracts`, `Events`, hook public, `PluginServiceProvider` và `Shared\Domain` (arch test R5). Chi tiết lớp: [bounded-contexts §4](bounded-contexts.md).

## 5. Phân lớp trong một request

```text
Route (nhóm theo bề mặt) → Middleware (correlation id, scope, auth)
  → Controller mỏng: Form Request + gọi Application/Contract + trả Resource/Inertia/View
  → Application service (transaction, khoá, idempotency)  ──► Hook trong flow (filter/validate/action)
  → Domain (quy tắc)  → Persistence (Eloquent, chỉ bảng của module)
  → sau commit: Domain event → listener Core, plugin (onEvent), bridge → Integration outbox
```

| Lớp | Được làm | Không được làm |
|---|---|---|
| Controller | Validate, gọi Application/Contract, trả Resource/Inertia/View | Query DB trực tiếp, tính nghiệp vụ (R9) |
| Application | Điều phối nhiều bước, transaction, gọi contract của module khác | Render HTML, gọi HTTP ngoài trong transaction checkout (R12) |
| Domain | Quy tắc, value object | Dùng Eloquent/Facade/HTTP (R8) |
| Theme / Blade / Vue | Hiển thị DTO đã chuẩn bị | Query DB, gọi repository, tính giá/tồn (R10) |

> **Dấu hiệu vi phạm tầng**: thấy `::query()`, model Eloquent hay repository trong file `.blade.php`/`.vue`. Dữ liệu phải được chuẩn bị từ Application/Presenter. (Bài học của hệ tham chiếu: view email từng tự tra DB, bản ghi bị xoá thì mail lỗi fatal.)

## 6. Số liệu (đếm từ source)

| Hạng mục | Hiện tại | Nguồn xác thực |
|---|---|---|
| Module Core | 18 | `config/modules.php` |
| Plugin và tác vụ định kỳ | Thay đổi theo manifest và code | `custom/plugin/*/vanishop.json`, module service providers |
| Queue | `search`, `fulfillment`, `notifications` và queue mặc định | Job declarations trong `modules/` và `custom/plugin/` |

> Số liệu chi tiết dễ trôi; hãy đếm lại từ source trước khi ghi vào tài liệu. Chỗ tài liệu khác mâu thuẫn với code: sửa tài liệu theo code (R24) và ghi vào [status](../00-overview/status.md).
