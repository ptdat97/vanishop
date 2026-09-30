# Đánh giá Commerce Kernel theo hướng microkernel (2026-10-13)

> Phạm vi: đối chiếu **code hiện tại** (sau slice Notification, commit `666d79a`) với mô hình microkernel mà [commerce-kernel](commerce-kernel.md), [extension-model](../04-extension/extension-model.md), [plugin-system](../05-plugin/plugin-system.md) và ADR-003/004 đặt ra: Core nhỏ, giữ invariant, mọi capability khác cắm vào qua Extension Points + Plugin system.
> Số liệu lấy bằng lệnh trên repo; mỗi phát hiện ghi kèm vị trí trong code.

## 1. Kết luận

VaniShop **không phải microkernel thuần** mà là **modular monolith có lõi mở rộng kiểu microkernel**. Lớp đóng vai trò "microkernel" là module `Extension` (khoảng 2k dòng): registry, lifecycle plugin, hook, kích hoạt theo scope. Toàn bộ Core gồm 20 module, khoảng 31k dòng PHP (không tính test), trong đó 18 là module nghiệp vụ — một lõi "dày". Plugin mới có 6 cái, khoảng 1,3k dòng.

Với thương mại điện tử, lõi dày là **đúng**. Reservation, state machine đơn, tiền, snapshot và idempotency phải chạy trong cùng transaction, nên không thể đẩy ra plugin. Slice 10–Notification đã chứng minh mô hình chạy được: 6 plugin (VietQR, GHN, PromotionRules, SMS, ZNS, HelloWorld) cắm vào mà **không chạm tầng nội bộ** của Core, và arch test R5 giữ được điều đó.

Tuy vậy, **bề mặt mở rộng (public API cho plugin) chưa đủ chặt để giao cho đội khác hoặc bên thứ ba viết plugin**. Có năm lỗ hổng chính:

1. Một nửa số tag extension point nằm trong tầng `Application`, là tầng plugin bị cấm import.
2. Plugin không có cách nghe domain event theo scope brand.
3. Strategy và cấu hình plugin chỉ chọn được toàn cục qua `.env`, không theo brand.
4. Core hard-code mã capability `cod` ở 4 module.
5. Không có kỷ luật version cho contract: Core vẫn `0.1.0` sau nhiều lần đổi contract.

Nên xử lý nhóm P0 (§4) **trước** khi mở rộng thêm plugin (slice 12–15).

## 2. Bảng điểm

| Tiêu chí microkernel | Đánh giá | Bằng chứng |
|---|---|---|
| Ranh giới Core/Plugin rõ, có tiêu chí | **Tốt** | Tiêu chí 3 câu hỏi ([commerce-kernel §1](commerce-kernel.md)); Core chỉ giữ mặc định tối thiểu (COD, chuyển khoản tay, flat rate, vận đơn tay, email) |
| Hướng phụ thuộc một chiều | **Tốt** | Arch test R4 (Core không dùng `Plugin\`), R5 (plugin chỉ dùng `Contracts`/`Events`/`Shared\Domain`), rule downstream cho 11 module |
| Cơ chế đăng ký implementation | **Tốt** | `Extensions::tag()` / `contribute()` / `tagged()` — plugin chỉ có hiệu lực ở owner/brand/channel được bật (`ScopedExtensions`) |
| Độ phủ extension point | **Khá** | 17 contract đã chạy; 4 contract thiết kế chưa có (`FulfillmentMethod`, `StorefrontBlock`, `ErpConnector`, `DashboardWidget`); 10/16 hook; 5/14 registry của `PluginServiceProvider` |
| Khả năng tìm thấy / dùng được từ plugin | **Yếu** | 8/17 tag đặt trong `Application`, plugin phải hard-code chuỗi (§3.1) |
| Cô lập theo brand (multi-brand) | **Trung bình** | Contract + hook: tốt. Domain event, strategy, cấu hình plugin: không theo scope (§3.2, §3.3) |
| Cô lập lỗi plugin | **Trung bình** | Loader bắt lỗi boot → `failed` + safe mode; slot UI bỏ qua lỗi; outbox/notification có retry. Lời gọi contract đồng bộ (quote, phương thức thanh toán, tìm kiếm) không được bảo vệ (§3.5) |
| Version / tương thích | **Yếu** | Core `0.1.0`, mọi plugin `^0.1`; không có abstract base, changelog, deprecation (§3.6) |
| Kiểm chứng plugin | **Trung bình** | Arch test tốt; contract test suite chỉ có 2/17 (`PaymentGatewayContract`, `ShippingCarrierContract`) |
| Vòng đời plugin | **Tốt** | discover/install/enable theo scope/disable/uninstall/failed, cache danh sách provider, safe mode; thiếu `upgrade`, `doctor`, settings |

## 3. Phát hiện chi tiết

### 3.1 Tag extension point nằm ngoài `Contracts` — plugin không tham chiếu được

Slice 10 đã chuyển `PromotionRule::TAG`, `PaymentGateway::TAG`, `ShippingCarrier::CARRIERS_TAG`, `ShippingRateProvider::TAG` vào contract. Còn **8 tag** vẫn nằm ở tầng mà R5 cấm plugin dùng:

| Tag | Hằng hiện tại (internal) |
|---|---|
| `vani.search.providers` | `Catalog\Application\Search\SearchManager::TAG` |
| `vani.fulfillment.sourcing` | `Fulfillment\Application\CarrierRegistry::SOURCING_TAG` |
| `vani.tax.calculators` | `Checkout\CheckoutServiceProvider::TAX_TAG` |
| `vani.totals.calculators` | `Checkout\Application\TotalsPipeline::TAG` |
| `vani.checkout.validators` | `Checkout\Application\CheckoutService::VALIDATORS_TAG` |
| `vani.inventory.strategies` | `Inventory\Application\ChannelAvailability::TAG` |
| `vani.returns.policies` | `Returns\Application\ReturnService::POLICIES_TAG` |
| `vani.pricing.strategies` | `Pricing\Application\StrategyPriceResolver::TAG` |

Plugin loyalty (cần `TotalsCalculator`) hay plugin chặn COD theo tỉnh (cần `CheckoutValidator`) sẽ phải gõ chuỗi tag bằng tay: đổi tên thì hỏng âm thầm.

### 3.2 Domain event không có "đăng ký theo scope" cho plugin

- `PluginServiceProvider` có `onFilter/onAction/onValidate/onSlot` (gắn plugin id, chỉ chạy khi plugin active), nhưng **không có `onEvent()`**. Plugin muốn phản ứng `OrderPlaced` (loyalty, e-invoice, abandoned cart) phải gọi thẳng `Event::listen`, và listener sẽ chạy **cho mọi brand**, kể cả brand không bật plugin.
- 17 event không mang `brandId`, nên kể cả khi có `onEvent()` cũng không xác định được scope: `PaymentCaptured`, `PaymentFailed`, `RefundCreated`, `RefundCompleted`, `ShipmentCreated`, `ShipmentStatusChanged`, `ReturnRequested`, `ReturnResolved`, `StockReserved/Released/Committed/Adjusted`, `AvailabilityChanged`, `CartUpdated` (Customer* là cấp Owner, hợp lý). Module Core bù bằng cách tra lại đơn (vd. `PublishDomainEvents`, `SendOrderNotifications`), nhưng plugin không nên phải làm vậy.

### 3.3 Strategy và cấu hình plugin chỉ chọn được toàn cục

- Strategy được chọn bằng `.env`: `VANI_PRICING_STRATEGY`, `VANI_TAX_CALCULATOR`, `VANI_INVENTORY_STRATEGY`, `VANI_RETURN_POLICY`, `VANI_FULFILLMENT_SOURCING`, `VANI_SEARCH_PROVIDER`. Plugin có thể đóng góp strategy mới, nhưng **không thể dùng cho riêng một brand**. Điều này mâu thuẫn với mục tiêu multi-brand (vd. brand A đổi trả 30 ngày, brand B 7 ngày).
- Cả 4 plugin có cấu hình đều đọc `.env` qua `Config/*.php` (`Ghn`, `VietQr`, `SmsBrandname`, `ZaloZns`). Cấu hình theo brand phải nhồi thành mảng trong config (vd. `brandnames` của SMS). Nguyên nhân gốc: `SettingsRepository` (settings kế thừa owner → pháp nhân → brand → kênh) và `settingsSchema()` vẫn ở mức Designed.

### 3.4 Core hard-code mã capability cụ thể

Khái niệm "thu tiền khi giao" được suy ra từ chuỗi `'cod'` ở 4 module:

- `Payment/Application/Listeners/AutoConfirmCodOrder.php`
- `Fulfillment/Application/FulfillmentService.php` (số tiền COD của vận đơn)
- `Checkout/Application/PaymentMethods.php` (`cod_pending`)
- `Ordering/Domain/CustomerStatus.php`

Plugin cổng thu tiền khi giao khác (vd. COD qua đối tác, trả sau tại cửa hàng) sẽ không được xử lý đúng. Cách sửa tổng quát: cờ trong `GatewayCapabilities` (vd. `collectsOnDelivery`), Core đọc cờ thay vì mã.

Trường hợp tương tự: `ConsentService::CHANNELS = ['email', 'sms', 'zns']`. Plugin kênh mới (`vani.webpush`) không có consent marketing tương ứng.

### 3.5 Cô lập lỗi chưa phủ lời gọi contract đồng bộ

| Điểm | Có bắt lỗi? |
|---|---|
| Boot plugin (`PluginLoader`) | Có → `failed`, Core vẫn chạy |
| Slot UI (`HookManager::onSlot`) | Có |
| Outbox worker, gửi thông báo, inbox | Có (retry/dead) |
| `PaymentService::availableMethods` (kiểm tra cổng khả dụng) | Có (đính chính 2026-10-14: bản đầu ghi nhầm "không") nhưng không có circuit breaker/plugin id |
| `ShippingOptions`, `TotalsPipeline`, `PromotionEvaluator`, `SearchManager`, `OtpService` | **Không** |

Với validate/totals khi đặt hàng, fail-fast là đúng thiết kế. Nhưng với các điểm **chỉ đọc/tuỳ chọn** thì một plugin lỗi đang làm hỏng cả flow: `isAvailable()` của cổng thanh toán, báo cước của hãng vận chuyển trong `quote`, provider tìm kiếm. Tài liệu đã hứa "health check fail → ẩn phương thức" ([plugin-system §8](../05-plugin/plugin-system.md)) nhưng chưa có cơ chế chung.

### 3.6 Không có kỷ luật version cho public API

- `config('vanishop.version') = 0.1.0`, cả 6 manifest `requires.vanishop: ^0.1`. Nghĩa là kiểm tra tương thích của loader luôn qua.
- Từ slice 11 tới nay contract đổi nhiều lần mà không tăng version:
  - Service contract (Core implement, plugin gọi) chỉ được thêm method, an toàn cho plugin gọi: `OrderReader` +2, `VariantDirectory` +1, `Carts` +2, `CustomerOrders` +3, `OrderWriter` +1, `Customers` +2, `InventorySync` mới.
  - Contract plugin implement thì đổi ngữ nghĩa: `OtpSender::send` giờ được phép ném `OtpDeliveryFailed`; `DeliveryResult` thêm kiểu `stale`.
- Chưa có `Abstract<Contract>` nào dù [extension-point-catalog §1](../04-extension/extension-point-catalog.md) hứa có. Vì vậy **mọi lần thêm method vào contract mà plugin implement đều là breaking change**. Cũng chưa có `CHANGELOG-extension.md` hay cơ chế deprecation.

### 3.7 Trùng lặp registry trong Core

`GatewayRegistry`, `CarrierRegistry`, `ChannelRegistry` (Notification), `ConnectorRegistry` (Integration), `PromotionRegistry`, `SearchManager` lặp lại cùng một mẫu: lấy `tagged()`, lọc `instanceof`, index theo `code()`, chạy trong scope brand (`runAs`). Một registry tổng quát, kiểu `Extensions::for($tag, $brandId, $interface)`, sẽ giảm lỗi khi thêm extension point mới.

### 3.8 Extension point đã thiết kế nhưng các slice gần đây bỏ qua

- Slice 11 (Integration) **không** triển khai hook `vani.integration.order_payload` có trong catalog, nên plugin không bổ sung được payload gửi ERP.
- Slice Customer/Notification không thêm registry `customerProfileTabs()`, `notificationTemplates()` như thiết kế.
- Hook `vani.order.before_create` (meta đơn từ plugin, cần cho `checkoutFields()`) vẫn chưa có.
- Hook `vani.catalog.listing.query`, `vani.catalog.product.view_data` cũng chưa có. Hook storefront slot chờ native storefront.

### 3.9 Core chứa tích hợp dịch vụ ngoài

`MeilisearchSearchProvider` nằm trong Core (`Catalog`) và gọi REST ra ngoài. Theo tiêu chí của chính [commerce-kernel §1](commerce-kernel.md), đây là plugin (`vani.search-meilisearch`), cùng loại với Algolia/Elastic trong plugin catalog. Mức ưu tiên thấp, nhưng nên sửa trước khi có thêm provider.

### 3.10 Chi phí runtime (hiện chấp nhận được)

- Mỗi request/job: `PluginActivation` chạy 2 truy vấn (`plugins`, `plugin_scopes`) cộng `Schema::hasTable('plugin_scopes')` (một truy vấn `information_schema` trên MySQL). Nên cache theo version trạng thái plugin, giống cache danh sách provider.
- `tagged()` khởi tạo lại mọi implementation mỗi lần gọi; nhỏ, nhưng nên memo theo scope.
- Hook chưa đo `hook_duration_ms`, filter chưa kiểm tra kiểu trả về (đã ghi "chưa có" trong extension-model).

## 4. Khuyến nghị theo mức ưu tiên

### P0: làm trước khi viết thêm plugin (trước slice 12–15)

| # | Việc | Phạm vi |
|---|---|---|
| 1 | Chuyển 8 tag trong §3.1 vào interface `Contracts` tương ứng; giữ hằng cũ làm alias `@deprecated` | 8 file, không đổi hành vi |
| 2 | `PluginServiceProvider::onEvent(Event::class, handler)`: chỉ gọi khi plugin active cho brand của event. Thêm `brandId` vào các event trong §3.2 (thêm field tuỳ chọn = minor) | Extension + 5 module |
| 3 | Thay `'cod'` bằng capability của gateway (`GatewayCapabilities::collectsOnDelivery`); danh sách kênh consent lấy từ `NotificationChannel` đã đăng ký | Payment, Fulfillment, Checkout, Ordering, Customer |
| 4 | `SettingsRepository` theo scope (owner → pháp nhân → brand → kênh) + `settingsSchema()` cho plugin; chọn strategy theo brand/kênh, `.env` chỉ là mặc định | Tenancy, Extension, 6 điểm chọn strategy |
| 5 | Kỷ luật version: chốt "public API v1" = catalog hiện tại, nâng Core lên `0.2.0`, tạo `CHANGELOG-extension.md`; contract plugin implement chỉ mở rộng qua interface mới hoặc `Abstract<Contract>` | Tài liệu + quy ước review |

### P1

| # | Việc |
|---|---|
| 6 | Contract test suite cho mọi contract plugin implement: `PromotionRule`, `PromotionAction`, `NotificationChannel`, `OtpSender`, `Connector`, `InboundHandler`, `SearchProvider`, `TaxCalculator`, `TotalsCalculator`, `CheckoutValidator`, `ReturnPolicy`, `InventoryStrategy`, `PricingStrategy`, `SourcingStrategy` (thư mục `Testing/` của Integration và Notification đang rỗng) |
| 7 | Cô lập lỗi cho lời gọi contract **tuỳ chọn**: helper chung (bắt lỗi, log kèm plugin id, circuit breaker theo plugin) dùng ở payment methods, shipping options, search |
| 8 | Registry tổng quát thay cho 6 registry lặp (§3.7) |
| 9 | Bổ sung hook còn thiếu mà roadmap cần: `vani.integration.order_payload`, `vani.order.before_create` + `checkoutFields()`, `vani.catalog.listing.query`; registry `scheduledTasks()`, `notificationTemplates()` |

### P2

| # | Việc |
|---|---|
| 10 | Chuyển Meilisearch thành plugin `vani.search-meilisearch` |
| 11 | Cache trạng thái kích hoạt plugin, bỏ `Schema::hasTable` mỗi request; memo `tagged()` theo scope |
| 12 | Metric `hook_duration_ms`, kiểm tra kiểu trả về của filter ở non-production; CLI `vani:plugin:upgrade`, `vani:plugin:doctor` |

## 5. Tiến độ xử lý

| Việc | Trạng thái |
|---|---|
| P0-1 Tag vào `Contracts` | ✅ 2026-10-13 — 8 tag chuyển lên interface (`SearchProvider::TAG`, `SourcingStrategy::TAG`, `TaxCalculator::TAG`, `TotalsCalculator::TAG`, `CheckoutValidator::TAG`, `InventoryStrategy::TAG`, `ReturnPolicy::TAG`, `PricingStrategy::TAG`), hằng cũ là alias `@deprecated`; arch test chặn định nghĩa tag ngoài `Contracts/` |
| P0-3 Bỏ hard-code capability | ✅ 2026-10-13 — `GatewayCapabilities::collectsOnDelivery` + `Payments::collectsOnDelivery()`; Checkout đặt `cod_pending` theo capability, các nơi khác đọc `payment_status` của đơn (không còn so `'cod'` trong Core); kênh consent là mã kênh bất kỳ đúng định dạng |
| P0-2 `onEvent()` + `brandId` | ✅ 2026-10-13 — `PluginServiceProvider::onEvent()` (lọc theo brand của event, chạy trong phạm vi brand, cô lập lỗi); `brandId` tuỳ chọn trên `PaymentCaptured/Failed`, `RefundCreated/Completed`, `ShipmentCreated/StatusChanged`, `ReturnRequested/Resolved`; event không có brand chỉ tới plugin bật ở owner |
| P0-5 Version public API | ✅ 2026-10-13 — Core `0.2.0`, mọi plugin `^0.2`; [CHANGELOG-extension](../04-extension/CHANGELOG-extension.md); snapshot public API trong arch test; chính sách mở rộng extension contract không cần abstract base |
| P1-8 Registry dùng chung | ✅ 2026-10-14 — `Extensions::implementations()` / `forBrand()` thay 6 vòng lặp registry |
| P1-7 Cô lập lỗi luồng tuỳ chọn | ✅ 2026-10-14 — `Extensions::call()` (log kèm plugin, circuit breaker 5 lỗi/phút → 5 phút); áp cho kiểm tra cổng thanh toán, báo cước, tìm kiếm (rơi về `database`). `OtpService` đã có dự phòng kênh từ slice Notification; validate/totals khi đặt hàng giữ fail-fast theo thiết kế |
| P1-9 Hook/registry còn thiếu | ✅ 2026-10-14 — `vani.integration.order_payload`, `vani.order.before_create` + `CheckoutRequest::$extra` (thay `checkoutFields()`), `vani.catalog.listing.query`, `PluginServiceProvider::schedule()`, `NotificationCatalog` (thay `notificationTemplates()`) |
| P1-6 Contract test suite | ✅ 2026-10-14 — 15 bộ mới (tổng 17) trong `Modules\<Ctx>\Testing`; Core defaults + 5 plugin chạy cùng bộ test; phát hiện và sửa 2 lỗi thật (`MailChannel` gửi email rỗng, `EmailOtpSender` không cho Core chuyển kênh) |
| P2-11 Chi phí runtime | ✅ 2026-10-14 — phạm vi bật của plugin trong cache dùng chung (không truy vấn DB khi cache ấm; bỏ `Schema::hasTable`), xoá khi trạng thái đổi; ghi nhớ danh sách implementation có hiệu lực theo (tag, phạm vi) trong request/job (không ghi nhớ instance — implementation `bind` đọc cấu hình lúc tạo) |
| P0-4 Settings theo scope | ✅ 2026-10-14 — bảng `settings` + contract `Settings` (Tenancy, kế thừa kênh → brand → pháp nhân → owner, secret mã hoá); 5 điểm chọn strategy nghiệp vụ đọc cấu hình theo kênh/brand (`.env` là mặc định, cấu hình sai/plugin tắt → mặc định); `PluginServiceProvider::settings()` + Admin → Cấu hình tự sinh form (lựa chọn lấy từ extension point); `vani.sms-brandname` đọc brandname theo brand |

## 6. Những điều **không** nên làm

- **Không thu nhỏ Core bằng cách đẩy invariant ra plugin**: reservation, state machine, totals guard, snapshot, outbox phải ở lại Core (R6, R14, R15).
- **Không tách module Core thành service riêng** (R19): lợi ích microkernel đến từ ranh giới contract, không đến từ ranh giới mạng.
- **Không biến Notification/Integration/Returns thành plugin**: chúng là khung (extension point + primitive) cho nhiều plugin, đúng tiêu chí 2 của Core.
