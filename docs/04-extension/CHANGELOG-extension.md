# Changelog — public API cho plugin

Ghi mọi thay đổi của extension point public: `Modules\*\Contracts`, `Modules\*\Events`, `PluginServiceProvider`, hook trong `hooks.php`, registry. Nguồn đối chiếu tự động: `tests/Architecture/public-api.snapshot` (test đỏ khi API đổi mà snapshot chưa cập nhật). Chính sách: [extension-model §5](extension-model.md).

Plugin khai báo `requires.vanishop` theo Composer semver. Ở giai đoạn `0.x`, tăng số giữa (`0.1` → `0.2`) được coi là **có thể phá vỡ**, nên `^0.1` không nhận Core `0.2.x`.

## 0.3.5 — 2026-10-02

Đợt W4b của [extension-surface-v2](extension-surface-v2.md): cổng giữ tiền rồi thu sau. Chỉ thêm.

### Thêm
- `Payment\Contracts\CapturesLater` (interface bổ sung tuỳ chọn cho `PaymentGateway`: `capture()`, `void()`, idempotent theo key) — Core kiểm tra bằng `instanceof`.
- `GatewayCallback::AUTHORIZED`; trạng thái payment `authorized`; event `Payment\Events\PaymentAuthorized`.
- `Payments::captureAuthorized($paymentId, $source)`; mã lỗi `payment.capture_failed`.
- Bộ contract test `PaymentGatewayContract`: cổng `CapturesLater` được trả callback `authorized`, phải capture/void idempotent.

### Hành vi
- Callback `authorized` → đơn xác nhận, `orders.payment_status = authorized`; vận đơn rời kho (`picked_up`) → Core thu tiền (`VANI_PAYMENT_CAPTURE_ON=shipped`, mặc định; `manual` = nhân viên bấm "Thu tiền" ở Admin thanh toán). Thu lỗi → giữ `authorized` + log.
- Đơn huỷ khi đang giữ tiền → `void` ở cổng, payment `cancelled` (không tạo hoàn tiền).

## 0.3.4 — 2026-10-02

Đợt W4a của [extension-surface-v2](extension-surface-v2.md): tuỳ chọn dòng giỏ, ngữ cảnh khuyến mãi. Chỉ thêm.

### Thêm
- `Cart\Contracts\CartLineOption` (tag `vani.cart.line_options`) + `InvalidCartLineOption`: tuỳ chọn `options.<plugin-id>.<field>` khi thêm dòng (Storefront API `POST /carts/{id}/lines`, form native); cùng variant khác tuỳ chọn là hai dòng; tuỳ chọn không đổi giá. Mã lỗi `cart.option_unknown`, `cart.option_invalid`.
- `Carts::addLine(..., array $options = [])`; field tuỳ chọn `options` trên `CartLineView`, `CartLineDraft`, `TotalsLine`, `OrderLineDraft`, `OrderLineData`; `PromotionLine::$variantId`.
- Hook `vani.checkout.context` (filter → `PromotionContext::$attributes`).
- Slot `vani.storefront.pdp.add_to_cart_fields` (trong form thêm giỏ).

### Đổi hành vi
- `TotalsLine::$key` / `PromotionLine::$key` là **id dòng giỏ** (trước: id variant) vì một variant có thể ở nhiều dòng; dùng `variantId` khi cần variant. Giữ hàng cộng số lượng theo variant.
- `order_lines.meta.options` chụp tuỳ chọn của dòng (hiển thị ở Admin đơn, trang cảm ơn, API đơn).

## 0.3.3 — 2026-10-02

Đợt W3 của [extension-surface-v2](extension-surface-v2.md): route và trang storefront của plugin, giỏ bị bỏ quên. Chỉ thêm.

### Thêm
- `PluginServiceProvider::storefrontRoutes()` — `/api/storefront/v1/x/{slug}/…` (middleware Storefront API của Core + plugin phải bật).
- `PluginServiceProvider::storefrontPages()` — trang native `/p/{slug}/…` trong layout theme; `storefrontViews()` — view của plugin, theme override tại `custom/theme/<theme>/plugins/{slug}/`.
- Event `Cart\Events\CartAbandoned` (`cartPublicId`, `customerId`, `itemCount`, `subtotal`, `currency`, `lastActivityAt`) — lệnh `vani:cart:detect-abandoned` (5 phút/lần, ngưỡng `VANI_CART_ABANDONED_AFTER_MINUTES`, mặc định 60), chỉ giỏ của khách còn hàng, một lần mỗi đợt không hoạt động.
- Đường dẫn `p` vào `vanishop.reserved_paths`.

## 0.3.2 — 2026-10-02

Đợt W2 của [extension-surface-v2](extension-surface-v2.md): plugin mở rộng màn hình Admin của Core. Chỉ thêm.

### Thêm
- `PluginServiceProvider::adminFormSection()`, `adminColumn()`, `adminAction()`, `adminTab()`, `adminFilter()` — tài nguyên `product`, `order`, `customer`.
- `Extension\Contracts\Data\FieldDefinition` (string, text, int, bool, select, date) — Core validate trước khi gọi `save` của plugin.
- Service contract `Extension\Contracts\AdminScreen` (module Core lấy phần mở rộng cho màn hình của mình).
- Route `POST /{admin}/extensions/{resource}/actions/{plugin}/{key}` (`ids[]`): Core kiểm tra quyền, ghi audit `plugin.action`.

## 0.3.1 — 2026-10-02

Đợt W1 của [extension-surface-v2](extension-surface-v2.md) ([ADR-030](../19-adr/ADR-030-extension-surface-v2.md)). Chỉ thêm — plugin `^0.3` chạy tiếp.

### Thêm
- `Storefront\Contracts\StorefrontEnricher` (tag `vani.storefront.enrichers`; tài nguyên `product_card`, `product`, `cart`, `order`) + bộ contract test `Storefront\Testing\StorefrontEnricherContract`. Dữ liệu gắn dưới `extensions.<plugin-id>` ở cả native và Storefront API. Implementation tham chiếu: `vani.hello-world`.
- Event `Ordering\Events\OrderCompleted` (`orderId`, `publicId`, `number`, `customerId`, `total`, `currency`) khi đơn chuyển `completed`.
- Slot storefront (`since 0.3.1` trong `hooks.php`): `header.nav`, `header.actions`, `footer.columns`, `plp.filters`, `pdp.gallery_after`, `checkout.contact_after`, `checkout.address_after`, `checkout.payment_after`.
- Slot storefront chấp nhận listener trả `null` (không hiện gì).

## 0.3.0 — 2026-10-02

Một cửa hàng ([ADR-028](../19-adr/ADR-028-single-store-brand-as-catalog.md)) — slice 12. Mọi plugin trong `custom/plugin` đã chuyển sang `^0.3`.

### Phá vỡ (một cửa hàng)
- Xoá module `Brand` (tenant) và `Channel` cùng contract `BrandDirectory`, `BrandData`, `ChannelDirectory`, `ChannelData`. Brand giờ là thực thể Catalog: `CatalogReader::brands()`, `CatalogReader::brand($slug)`.
- `ContextScope(Actor $actor, ?string $locale)`; `CurrentContext::brandIds()`/`channelId()` → `locale()`. Xoá `BelongsToBrand`, `BrandScope`, `BrandAccessDenied`.
- `Settings` một cấp: `get($ns, $key, $default)`, `set($ns, $key, $value)`, `forget($ns, $key)`, `explicit($ns)`; xoá `SettingsScope`, `Settings::current()`, tham số `scopes` của `SettingDefinition`.
- Plugin bật cho cả cửa hàng: `vani:plugin:enable/disable` bỏ `--scope`, manifest bỏ `scopes`; xoá `Extensions::forBrand()` (dùng `implementations()`); `onEvent()` không còn lọc theo brand.
- RBAC: `ScopeType` chỉ còn `Owner`, `Location`; xoá `ScopeRef::brand()`, `legalEntity()`, `Authorizer::accessibleBrandIds()`.
- Bỏ `brandId`/`channelId`/`legalEntityId` khỏi DTO và event: `PricingContext($now, ?$customerGroupId)`, `AvailabilityReader::forVariants()`, `InventoryStrategy::adjust($standardAts)`, `ReservationRequest($key, $lines, $ttl)`, `CartLineDraft`, `CartUpdated`, `PromotionContext`, `TotalsContext`, `Totals`, `PaymentContext($amount)`, `Payments::availableMethods($amount)`, `PaymentData`, `SourcingRequest`, `ShipmentData`, `ReturnContext`, `NotificationRequest`, `OutgoingMessage`, `IntegrationEvent`, `OutboxMessage`, `OrderPlaced($orderId, $publicId, $number, $customerId, $total, $currency)`, `OrderConfirmed`, `OrderCancelled`, event Payment/Fulfillment/Returns, `ConsentChanged`. Registry bỏ tham số brand: `ConnectorRegistry::connectors()`, `CarrierRegistry::sourcing()`, `ChannelRegistry::all()`, `ReturnService::policy()`.
- `OrderReader::customerHasPlacedOrder($customerId)`; `customerBrandStats` → `customerStats($customerId)`. `Customers::hasConsent($customerId, $channel, $purpose)`.
- Hook `vani.product.after_save` chỉ nhận `(styleId)`.
- Xoá hằng deprecated từ 0.2.0 (`SearchManager::TAG`, `CarrierRegistry::SOURCING_TAG`, …) — dùng `TAG` trên interface.

### Microkernel — plugin hệ thống (slice 12d, ADR-029)
- `Extension\Contracts\Requirement` (`AtLeastOne`, `ExactlyOne`); `Extensions::requires($tag, $requirement, $label)`, `requirements()`, `providers($tag)`. `PluginManager::disable()` từ chối tắt implementation đang bật cuối cùng của extension point bắt buộc; `vani:plugin:doctor` báo `required_extension_missing`.
- Manifest `"bundled": true`; `PluginManager::installBundled()`; lệnh `vani:install`.
- **Đổi hành vi:** COD, chuyển khoản, phí giao cố định, VAT VN rời Core thành plugin hệ thống `vani.cod`, `vani.bank-transfer`, `vani.shipping-flat-rate`, `vani.tax-vn-vat` (giữ mã `cod`, `manual_bank_transfer`, `standard`, `vn_vat_inclusive`). Cấu hình chuyển từ `vanishop.payment.cod.*`, `vanishop.payment.bank_transfer.*`, `vanishop.checkout.shipping.*`, `vanishop.tax.vat_rate_bp` sang `vani.cod.*`, `vani.bank-transfer.*`, `vani.shipping-flat-rate.*`, `vani.tax-vn-vat.*` + Admin → Cấu hình (biến `.env` giữ tên). Core thêm TaxCalculator `none` (dự phòng). Nguồn phí giao `ShippingOption::$source` của phí cố định là `vani.shipping-flat-rate` (trước: `core`).
- Checkout: không cổng nào khả dụng → issue `no_payment_method`.

### Carrier theo phương thức giao (slice 12c — phần Core)
- `OrderData::$shippingMethod` (`code`, `label`, `source`, `fee`).
- **Đổi hành vi:** vận đơn dùng carrier có `code()` = `shippingMethod.source` của đơn (dịch vụ = `shippingMethod.code` → `ShipmentData::$serviceCode`) khi carrier đang bật; ngược lại dùng `vanishop.fulfillment.default_carrier`.

### Native storefront (slice 12b)
- Slot storefront (`modules/Storefront/hooks.php`, `since 0.3`): `vani.storefront.layout.head`, `layout.body_end`, `plp.card_badges`, `pdp.after_title`, `pdp.after_price`, `pdp.after_add_to_cart`, `pdp.after_details`, `cart.after_lines`, `checkout.after_shipping`, `checkout.before_submit`, `order.after_summary`.
- `Storefront\Contracts\Data\SlotView` — phần tử plugin trả cho slot storefront.

### Thêm (một cửa hàng)
- `?int $brandId` (+ `brandName`) trên `SellableVariant`, `VariantData`, `ProductDocument`, `PromotionLine`, `TotalsLine`, `OrderLineDraft`, `OrderLineData`; `ProductSearchQuery::$brandIds` là bộ lọc (rỗng = mọi brand), `ProductSearchResult::$facets['brands']`, `ProductFilters::$brandSlugs`.
- `OrderDraft::$source`, `OrderData::$source`, `CheckoutRequest::$source` (`web`/`app`/`zalo`, header `X-Vani-Source`).
- Rule khuyến mãi `in_brands` (plugin `vani.promotion-rules`).


### Thêm
- `Extensions::implementations($tag, $interface, $key)` — registry dùng chung (lọc theo interface, đánh chỉ mục theo mã, chỉ plugin đang bật). Các registry của Core (`GatewayRegistry`, `CarrierRegistry`, `PromotionRegistry`, `SearchManager`, `ChannelRegistry`, `ConnectorRegistry`) dùng helper này.
- `Extensions::call($implementation, $call, $fallback, $operation)` — cô lập lỗi trên luồng tuỳ chọn + circuit breaker theo plugin (5 lỗi/phút → bỏ qua 5 phút).

- Hook: `vani.integration.order_payload` (chỉ thêm khoá), `vani.order.before_create` (orders.meta), `vani.catalog.listing.query`.
- `CheckoutRequest::$extra` (trường checkout của plugin theo plugin id; Storefront API `extra`), `OrderDraft::$meta`, `OrderData::$meta`.
- `PluginServiceProvider::schedule()` — tác vụ định kỳ chỉ chạy khi plugin đang bật.
- `Notification\Contracts\NotificationCatalog` + `Data\NotificationType` — loại tin và mẫu mặc định theo kênh (thay lớp nội bộ `NotificationTypes`).

- Bộ contract test cho plugin (`Modules\<Ctx>\Testing\*Contract::define()`): `PromotionRule`, `PromotionAction`, `TaxCalculator`, `TotalsCalculator`, `CheckoutValidator`, `ShippingRateProvider`, `ReturnPolicy`, `InventoryStrategy`, `PricingStrategy`, `SourcingStrategy`, `SearchProvider`, `NotificationChannel`, `OtpSender`, `Connector`, `InboundHandler` (cùng `PaymentGateway`, `ShippingCarrier` có từ trước) — [testing §6](../17-testing/testing.md).

- CLI `vani:plugin:upgrade`, `vani:plugin:doctor`.
- Hook: đo `hook_duration_ms` theo hook × plugin; filter trả sai kiểu → `HookReturnTypeMismatch` (strict) / bỏ kết quả (production).

- `Catalog\Contracts\ConfigurableSearchIndex` (interface tuỳ chọn cho SearchProvider có chỉ mục ngoài: `setupIndex()`).

### Đổi hành vi
- Meilisearch tách khỏi Core thành plugin `vani.search-meilisearch` (cùng biến env `MEILISEARCH_*`). `VANI_SEARCH_PROVIDER` trỏ tới provider không có hiệu lực → dùng `database` + cảnh báo (trước đây: lỗi).
- `MailChannel`: tin thiếu tiêu đề/nội dung → `permanent('mail.empty')` thay vì gửi email rỗng. `EmailOtpSender`: lỗi SMTP → `OtpDeliveryFailed` (Core chuyển sang kênh OTP kế tiếp).
- `PaymentGateway::isAvailable()` lỗi → ẩn cổng; `ShippingRateProvider::options()` lỗi → bỏ lựa chọn của provider đó; `SearchProvider::search()` lỗi → tìm bằng provider `database`. Plugin lỗi liên tục bị tạm bỏ qua trên các luồng này.

## 0.2.0 — 2026-10-13

Chốt bề mặt API sau slice 11, Customer, Notification và đợt P0 của [kernel-review](../02-architecture/kernel-review.md). Mọi plugin trong `custom/plugin` đã chuyển sang `^0.2`.

### Thêm
- Extension point: `Integration\Contracts\Connector`, `InboundHandler`; `Notification\Contracts\NotificationChannel`; `Customer\Contracts\OtpSender`.
- Service contract: `IntegrationEvents`, `Inbox`, `ExternalReferences`, `Mappings`, `Notifier`, `Customers`, `InventorySync`.
- Method mới trên service contract (Core implement): `OrderReader::changedSince`, `customerBrandStats`; `VariantDirectory::findBySkus`; `Carts::forCustomer`, `attachToCustomer`; `CustomerOrders::ofCustomer`, `showForCustomer`, `cancelForCustomer`; `OrderWriter::reassignCustomer`; `Payments::collectsOnDelivery`.
- Hằng `TAG` trên interface: `SearchProvider`, `SourcingStrategy`, `TaxCalculator`, `TotalsCalculator`, `CheckoutValidator`, `InventoryStrategy`, `ReturnPolicy`, `PricingStrategy`.
- `PluginServiceProvider::onEvent()` — nghe domain event theo phạm vi brand.
- Cấu hình theo phạm vi: `Tenancy\Contracts\Settings` (`get`, `current`, `set`, `forget`, `explicit`, `define`, `definitions`), `SettingsScope` (kênh → brand → pháp nhân → owner), `SettingDefinition`; `PluginServiceProvider::settings()` khai báo cấu hình của plugin (Admin → Cấu hình sinh form).
- `Extensions::select($tag, $code, $fallbackCode)` — chọn implementation theo mã, cấu hình trỏ tới implementation không có hiệu lực thì dùng mặc định.
- Field tuỳ chọn: `GatewayCapabilities::$collectsOnDelivery`; `?int $brandId` trên `PaymentCaptured`, `PaymentFailed`, `RefundCreated`, `RefundCompleted`, `ShipmentCreated`, `ShipmentStatusChanged`, `ReturnRequested`, `ReturnResolved`; `OrderData::$fulfillmentStatus`, `$placedAt`, `$updatedAt`, `recipient.email`.
- Kết quả mới: `Integration\Contracts\Data\DeliveryResult::stale()`.
- Ngoại lệ: `Customer\Contracts\OtpDeliveryFailed` — `OtpSender::send()` ném để Core thử kênh kế tiếp.
- Event: `CustomerRegistered`, `CustomerMerged`, `CustomerAnonymized`, `ConsentChanged`.

### Đổi hành vi
- Core không còn suy "thu tiền khi giao" từ mã cổng `cod`; cổng plugin muốn được xử lý như COD phải khai báo `GatewayCapabilities::$collectsOnDelivery = true`.
- Strategy chọn theo cấu hình phạm vi (`core.pricing.strategy`, `core.inventory.strategy`, `core.tax.calculator` theo kênh/brand; `core.returns.policy`, `core.fulfillment.sourcing` theo brand); biến `.env` cũ chỉ còn là mặc định. Search provider vẫn theo `.env` (lựa chọn hạ tầng).
- Kênh consent là mã kênh bất kỳ đúng định dạng `^[a-z][a-z0-9_]{1,31}$` (trước đây chỉ `email|sms|zns`).

### Deprecated (xoá ở 0.3.0)
- `SearchManager::TAG`, `CarrierRegistry::SOURCING_TAG`, `CheckoutServiceProvider::TAX_TAG`, `TotalsPipeline::TAG`, `CheckoutService::VALIDATORS_TAG`, `ChannelAvailability::TAG`, `ReturnService::POLICIES_TAG`, `StrategyPriceResolver::TAG` — dùng hằng `TAG` trên interface tương ứng.

## 0.1.0 — 2026-09-28

Phiên bản đầu (slice 0–10).
