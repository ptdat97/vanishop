# Changelog — public API cho plugin

Ghi mọi thay đổi của extension point public: `Modules\*\Contracts`, `Modules\*\Events`, `PluginServiceProvider`, hook trong `hooks.php`, registry. Nguồn đối chiếu tự động: `tests/Architecture/public-api.snapshot` (test đỏ khi API đổi mà snapshot chưa cập nhật). Chính sách: [extension-model §5](extension-model.md).

Plugin khai báo `requires.vanishop` theo Composer semver. Ở giai đoạn `0.x`, tăng số giữa (`0.1` → `0.2`) được coi là **có thể phá vỡ**, nên `^0.1` không nhận Core `0.2.x`.

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
