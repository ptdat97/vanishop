# Vòng đời request, middleware, tác vụ nền

> Trạng thái: **Partially Implemented**, mô tả code đang có. Kiểm chứng từ `bootstrap/app.php`, `bootstrap/providers.php`, `modules/Shared/Support/ModuleServiceProvider.php`, `modules/Extension/ExtensionServiceProvider.php`, `PluginServiceProvider`, provider của Payment/Fulfillment/Integration/Storefront/Channel/Customer. Bản đồ thành phần: [system-map](system-map.md).

> **Định hướng mới ([ADR-028](../19-adr/ADR-028-single-store-brand-as-catalog.md))**: một cửa hàng, brand là thuộc tính catalog. Tài liệu này mô tả **code hiện tại** (vẫn có module Brand/Channel, brand workspace, `X-Vani-Channel`); các phần đó sẽ gỡ ở slice 12 ([store-and-brand §6](../12-store/store-and-brand.md)).

## 1. Khởi động (boot)

```text
bootstrap/providers.php
  AppServiceProvider
  ModuleServiceProvider ──► register() từng module theo config/modules.php
                             Shared … Identity → Extension ──► PluginLoader::load() (trong register)
                                                                 ├─ safe mode? → không nạp plugin nào
                                                                 └─ đọc bootstrap/cache/vanishop-plugins.php
                                                                    (plugin installed/enabled/disabled, thứ tự phụ thuộc)
                                                                    → register provider của từng plugin, lỗi → failed
                             … Catalog → … → Storefront
  boot(): module Core và plugin boot cùng vòng
    ├─ module: route, hook Core, listener, RateLimiter, schedule
    └─ plugin: contribute(), onFilter/onEvent…, adminRoutes/webhookRoutes, settings(), schedule()
```

| Điểm | Hành vi | Vì sao |
|---|---|---|
| Plugin nạp trong `register()` của Extension | Provider plugin có mặt trước vòng `boot()` | Plugin dùng được binding của mọi module, route plugin đăng ký cùng lúc với route Core |
| Danh sách plugin đọc từ file cache | Không truy vấn DB lúc boot | Cache được dựng lại mỗi khi install/enable/disable/failed |
| Plugin `installed`/`disabled` vẫn được `register()` | Route webhook/Admin cấu hình chạy được | Listener và implementation chỉ **có hiệu lực** khi plugin bật trong scope hiện tại |
| Lỗi trong `register()`/`boot()` của plugin | Ghi log, plugin → `failed`, Core tiếp tục boot | [plugin-system §8](../05-plugin/plugin-system.md) |
| `VANI_PLUGINS_SAFE_MODE=true` | Không nạp plugin nào | Cứu hộ khi plugin làm hỏng hệ thống |

> Khác hệ tham chiếu ([reference-comparison](reference-comparison.md)): VaniShop **không** cần plugin boot "sau cùng để ghi đè" vì plugin không ghi đè gì. Plugin chỉ **đóng góp** implementation (tag) và **nghe** hook đã khai báo; thứ tự giữa listener do `priority` quyết định, không do thứ tự nạp provider.

## 2. Middleware

### 2.1 Toàn cục và ưu tiên

| Middleware | Vị trí | Tác dụng |
|---|---|---|
| `AssignCorrelationId` | Prepend toàn cục | Mỗi request có correlation id (header + log context), R22 |
| `ConfigureAdminSession` | Đầu nhóm `web` | Request vào đường dẫn Admin dùng cookie phiên riêng (ADR-020); phải chạy trước `StartSession` |
| `ResolveStaffContext`, `ResolveAdminBrand`, `ResolveChannel`, `ResolveApiChannel` | Chèn **trước** `SubstituteBindings` trong danh sách ưu tiên | Scope (nhân viên → brand workspace; kênh storefront) có trước route model binding, nên model có phạm vi brand tự lọc: id của brand khác → 404 |

### 2.2 Nhóm route theo bề mặt

| Bề mặt | Middleware | Rate limit | Stateful |
|---|---|---|---|
| Admin (`loadAdminRoutes`) | `web` → `vani.admin` (`AdminGate`: IP allowlist, noindex… + Inertia) → `auth:staff` → `vani.staff-context` | Đăng nhập Admin: giới hạn số lần sai (`StaffLoginRequest`) | Có (phiên Admin, CSRF) |
| Admin brand workspace (`loadBrandWorkspaceRoutes`) | như Admin + `vani.admin-brand`; prefix `/{admin}/{section}/{brand}` | — | Có |
| Admin của plugin (`adminRoutes`) | như Admin + `vani.plugin-active:{id}` (404 khi plugin không active); prefix `/{admin}/plugins/{slug}` | — | Có |
| Storefront API (`loadStorefrontApiRoutes`) | `api` → `throttle:storefront-api` → `vani.api-channel` (kênh từ `X-Vani-Channel`) | 240/phút/IP; tạo giỏ 30, checkout 20, tra đơn 10, đăng nhập khách 20 | Không (token) |
| Integration API | `api` → `vani.integration-client` (HMAC, scope, IP) → `throttle:integration-api` | Theo client | Không |
| Callback thanh toán | `api` → `throttle:payment-callbacks`; `/api/payments/{gateway}/callback` | 600/phút/IP | Không |
| Webhook vận chuyển | `api` → `throttle:shipping-webhooks`; `/api/shipping/{carrier}/webhook` | 600/phút/IP | Không |
| Webhook của plugin (`webhookRoutes`) | `api`; `/api/integrations/{slug}/…` | Plugin tự đặt | Không |
| Storefront native | Designed: `web` → phiên khách (một website, không xác định kênh/brand theo URL) | Designed | Có |

Nhóm `api` không có phiên và CSRF, nên callback/webhook không cần "loại trừ CSRF"; bù lại **bắt buộc xác minh chữ ký** ở plugin ([payment-gateway](../05-plugin/contracts/payment-gateway.md), [shipping-carrier](../05-plugin/contracts/shipping-carrier.md)).

### 2.3 Lỗi

| Request | Renderer | Định dạng |
|---|---|---|
| `api/*` hoặc `Accept: application/json` | `ApiErrorRenderer` | Lỗi chuẩn có mã (`BusinessRuleViolation` → mã nghiệp vụ) ([api](../06-api/api.md)) |
| Admin (web) | `WebBusinessRuleRenderer` | Lỗi nghiệp vụ hiển thị như lỗi form |

## 3. Luồng một request ghi (ví dụ đặt hàng qua Storefront API)

```text
POST /api/storefront/v1/checkout/{cart}/orders   (Idempotency-Key, expected_total)
  → correlation id → throttle → kênh (X-Vani-Channel) → token giỏ
  → Controller: Form Request → Checkout::placeOrder()
      DB transaction
        ├─ khoá giỏ, tính totals (TotalsCalculator theo priority, guard cuối)
        ├─ hook validate: vani.checkout.before_validate / after_validate   (không I/O mạng)
        ├─ reserve tồn (khoá dòng tồn, R14)
        ├─ hook filter: vani.order.before_create (meta của plugin)
        ├─ OrderWriter (snapshot) · ghi lượt khuyến mãi
        └─ hook action: vani.order.after_create (chỉ ghi DB của plugin)
      commit
  → sau commit: OrderPlaced → listener Core, plugin onEvent(),
                bridge tích hợp → event feed + outbox (transaction riêng; khe hở được bù bằng
                vani:integration:reconcile-orders)
  → khởi tạo thanh toán (PaymentGateway::initiate, sau commit) → trả payment.action
```

Chi tiết nghiệp vụ: [cart-checkout](../03-domains/cart-checkout.md), [consistency](consistency.md).

## 4. Tác vụ định kỳ và hàng đợi

Core đăng ký lịch trong provider của module (`withoutOverlapping()->onOneServer()`); plugin dùng `PluginServiceProvider::schedule()`, tác vụ chỉ chạy khi plugin bật ở ít nhất một scope.

| Lệnh | Tần suất | Module |
|---|---|---|
| `vani:inventory:release-expired` | mỗi phút | Inventory |
| `vani:payment:expire`, `vani:payment:reconcile` | mỗi phút | Payment |
| `vani:integration:dispatch`, `vani:integration:process-inbox` | mỗi phút | Integration |
| `vani:integration:reconcile-orders` | mỗi giờ | Integration |
| `vani:orders:complete-delivered` | mỗi giờ | Fulfillment |
| `vani:idempotency:prune` | mỗi giờ | Shared |
| `vani:cart:prune` | 03:30 hằng ngày | Cart |

Hàng đợi: `search` (đồng bộ index), `fulfillment` (đặt vận đơn), `notifications` (gửi tin); còn lại `default`. Driver hiện là `database`; Redis + Horizon: Designed ([operations](../18-operations/operations.md)). Mọi job/listener phải idempotent (R18).

> Tồn kho **không phụ thuộc** tác vụ nền để đúng: reservation hết hạn chỉ làm ATS thấp hơn thực tế cho tới khi `release-expired` chạy, không bao giờ gây oversell. Đây là điểm VaniShop giữ khác hệ tham chiếu ([reference-comparison §3](reference-comparison.md)).
