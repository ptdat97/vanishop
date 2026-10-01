# Bản đồ hệ thống (tham chiếu mức mã nguồn)

> Trạng thái: **Partially Implemented**, mô tả **code đang có**; bề mặt nào chưa có code ghi rõ `Designed`. Kiểm chứng từ `bootstrap/app.php`, `config/modules.php`, `modules/Shared/Support/ModuleServiceProvider.php`, `modules/*/*ServiceProvider.php`. Bổ sung cho [overview](overview.md) (vì sao chọn kiến trúc này) và [bounded-contexts](bounded-contexts.md) (ranh giới nghiệp vụ).

> **Định hướng mới ([ADR-028](../19-adr/ADR-028-single-store-brand-as-catalog.md))**: một cửa hàng, brand là thuộc tính catalog. Tài liệu này mô tả **code hiện tại** (vẫn có module Brand/Channel, brand workspace, `X-Vani-Channel`); các phần đó sẽ gỡ ở slice 12 ([store-and-brand §6](../12-store/store-and-brand.md)).

## 1. Năm bề mặt, một lõi

Mọi bề mặt gọi **cùng Application layer / Contracts** của module. Không bề mặt nào tự tính giá, tồn, khuyến mãi hay trạng thái đơn.

| Bề mặt | Đối tượng | Prefix | Auth | Nạp bởi | Trạng thái |
|---|---|---|---|---|---|
| **Storefront native** (Blade SSR) | Khách trên web | `/…` (một website, [store-and-brand §4](../12-store/store-and-brand.md)) | Phiên web (khách) | Theme `custom/theme/*` + controller trong `modules/Storefront` | Designed ([ADR-025](../19-adr/ADR-025-native-storefront-ssr-slots.md)) |
| **Storefront API** (JSON) | Headless, mobile, Zalo Mini App | `/api/storefront/v1` | Khách vãng lai: token giỏ/đơn ([ADR-022](../19-adr/ADR-022-guest-cart-token.md)); đã đăng nhập: Bearer ([ADR-024](../19-adr/ADR-024-customer-api-token.md)) | `loadStorefrontApiRoutes()` (Storefront, Customer) | Implemented |
| **Admin** (Inertia + Vue) | Nhân viên | `/{VANI_ADMIN_PATH}` ([ADR-020](../19-adr/ADR-020-admin-path-no-2fa.md)) | Guard `staff`, phiên Admin riêng | `loadAdminRoutes()`, `loadBrandWorkspaceRoutes()` | Implemented |
| **Admin API** (JSON) | App quản trị, công cụ nội bộ | `/api/admin/v1` | Designed | — | Designed |
| **Integration API** (JSON) | ERP, POS, đối tác | `/api/integration/v1` | Key id + chữ ký HMAC, scope (data scope brand: gỡ ở slice 12) | `IntegrationServiceProvider` | Implemented |

Ngoài ra có hai cửa vào **từ hệ thống ngoài** (không phải bề mặt cho người dùng): callback cổng thanh toán `/api/payments/{gateway}/callback` và webhook hãng vận chuyển `/api/shipping/{carrier}/webhook`. Cả hai là route **chung**; plugin chỉ cung cấp phần xác minh + chuẩn hoá (`verifyCallback`, `parseWebhook`), Core ghi nhận. Webhook riêng của plugin nằm dưới `/api/integrations/{slug}/…`.

> Nguyên tắc bắt nguồn từ hệ tham chiếu ([reference-comparison L1](reference-comparison.md)): **thêm một bề mặt = thêm lớp trình bày mỏng**, không thêm logic. Ví dụ: Storefront API và storefront native dùng cùng `ProductViews`, `CartPresenter`, `CheckoutPresenter` của module `Storefront` ([ADR-021](../19-adr/ADR-021-storefront-composition-module.md)).

## 2. Ba namespace

| Namespace | Thư mục | Vai trò | Được sửa khi thêm capability? |
|---|---|---|---|
| `App\` | `app/` | Khung Laravel: provider nạp module, middleware Inertia, dashboard tối thiểu, logging | Hạn chế, không chứa nghiệp vụ |
| `Modules\` | `modules/` | **Commerce Kernel**: 20 module | Chỉ khi thêm **extension point tổng quát** (R1) |
| `Plugin\` | `custom/plugin/` | Capability nghiệp vụ | Có |

Theme storefront nằm ở `custom/theme/` (hiện trống).

## 3. Module và thứ tự nạp

`App\Providers\ModuleServiceProvider` đăng ký provider của từng module theo **đúng thứ tự** trong `config/modules.php` (upstream trước):

```text
Shared → Tenancy → Brand → Channel → Identity → Extension
→ Catalog → Pricing → Inventory → Cart → Promotion → Ordering → Customer
→ Payment → Checkout → Fulfillment → Returns → Notification → Integration → Storefront
```

| Nhóm | Module | Vai trò |
|---|---|---|
| Nền tảng | Shared, Tenancy, Brand, Channel, Identity | Money, context/scope, settings theo scope, brand/kênh, nhân viên + RBAC + audit |
| Microkernel | **Extension** | Hook registry, plugin loader, lifecycle, kích hoạt theo scope, registry implementation (`Extensions`) |
| Thương mại | Catalog, Pricing, Inventory, Cart, Promotion, Ordering, Customer, Payment, Checkout, Fulfillment, Returns | Primitives + invariants |
| Khung dùng chung | Notification, Integration | Extension point + primitive cho nhiều plugin |
| Tầng ghép | Storefront | Ghép dữ liệu từ contract của nhiều module cho bề mặt khách, không có bảng |

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

| Hạng mục | Số lượng | Lệnh đếm |
|---|---|---|
| Module Core | 20 | `config/modules.php` |
| Interface trong `Contracts/` | 50 | `grep -rl "^interface " modules/*/Contracts/*.php \| wc -l` |
| Domain event | 26 | `ls modules/*/Events/*.php \| wc -l` |
| Hook public đã khai báo | 13 | `grep -h "'vani\." modules/*/hooks.php \| grep -c "=> \["` |
| PHP Core (không tính test) | ~33,6k dòng | `find modules -name '*.php' -not -path '*/Tests/*' \| xargs cat \| wc -l` |
| Plugin | 6 (~1,4k dòng) | `ls custom/plugin` |
| Tác vụ định kỳ của Core | 9 | `grep -rh "schedule->command('vani" modules --include='*ServiceProvider.php' \| grep -v einvoice` |
| Queue riêng | `search`, `fulfillment`, `notifications` (còn lại: `default`) | `grep -rn onQueue modules` |

> Số liệu **sẽ trôi**. Khi cập nhật tài liệu, chạy lại lệnh thay vì chép số cũ. Chỗ tài liệu khác mâu thuẫn với code: sửa tài liệu theo code (R24) và ghi vào [status](../00-overview/status.md).
