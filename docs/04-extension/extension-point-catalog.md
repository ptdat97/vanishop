# Danh mục Extension Points (public API v1)

> Trạng thái: **Designed**. Bảng này là **nguồn duy nhất** liệt kê extension point public. Thêm/đổi phải theo [compatibility policy](extension-model.md).

## 1. Contract: capability thay thế được

Plugin đăng ký bằng tag container. Registry của Core lọc theo trạng thái bật của plugin trong scope hiện tại (brand/channel/legal entity).

| Contract | Tag | Context | Mặc định trong Core | Tài liệu |
|---|---|---|---|---|
| `PaymentGateway` | `vani.payment.gateways` | Payment | `cod`, `manual_bank_transfer` | [payment](../10-payment/payment.md) |
| `ShippingCarrier` | `vani.shipping.carriers` | Fulfillment | `flat_rate`, `manual` | [fulfillment](../09-order/fulfillment.md) |
| `FulfillmentMethod` | `vani.fulfillment.methods` | Fulfillment | `delivery` | [fulfillment](../09-order/fulfillment.md) |
| `SourcingStrategy` | `vani.fulfillment.sourcing` | Fulfillment | `priority_first_fit` | [fulfillment](../09-order/fulfillment.md) |
| `InventoryStrategy` | `vani.inventory.strategies` | Inventory | `standard` (ATS = on_hand − reserved − safety) | [inventory](../08-inventory/inventory.md) |
| `PricingStrategy` | `vani.pricing.strategies` | Pricing | `price_list_priority`: **Implemented** (chọn bằng `VANI_PRICING_STRATEGY`) | [catalog-pricing](../03-domains/catalog-pricing.md) |
| `TaxCalculator` | `vani.tax.calculators` | Checkout | `vn_vat_inclusive` | [cart-checkout](../03-domains/cart-checkout.md) |
| `TotalsCalculator` | `vani.totals.calculators` | Checkout | subtotal, promotion, shipping, tax, rounding | [cart-checkout](../03-domains/cart-checkout.md) |
| `CheckoutValidator` | `vani.checkout.validators` | Checkout | price, stock, address | [cart-checkout](../03-domains/cart-checkout.md) |
| `PromotionRule` | `vani.promotion.rules` | Promotion | — (rule do plugin cung cấp) | [promotion](../03-domains/promotion.md) |
| `PromotionAction` | `vani.promotion.actions` | Promotion | `percent_off`, `amount_off` (primitive) | [promotion](../03-domains/promotion.md) |
| `ReturnPolicy` | `vani.returns.policies` | Returns | `days_window` | [order §7](../09-order/order.md) |
| `SearchProvider` | `vani.search.providers` | Catalog | `database`, `meilisearch`: **Implemented** | [catalog-pricing](../03-domains/catalog-pricing.md) |
| `OtpSender` | `vani.auth.otp_senders` | Customer | `email` | [customer](../03-domains/customer.md) |
| `NotificationChannel` | `vani.notification.channels` | Notification | `mail` | [customer §4](../03-domains/customer.md) |
| `Connector` | `vani.integration.connectors` | Integration | — | [integration-platform](../11-integration/integration-platform.md) |
| `ErpConnector` (extends `Connector`) | `vani.integration.erp` | Integration | — | [erp-integration](../11-integration/erp-integration.md) |
| `StorefrontBlock` | `vani.content.blocks` | Content | hero, product grid, rich text, banner | [storefront](../14-storefront/storefront.md) |
| `DashboardWidget` | `vani.admin.widgets` | Reporting | doanh số, đơn mới | — |

Mỗi contract có abstract base (`Abstract<Contract>`) cung cấp default cho method tuỳ chọn, và bộ **contract test** ([testing §6](../17-testing/testing.md)).

## 2. Service contract: plugin gọi Core

| Contract | Chức năng | Context |
|---|---|---|
| `CatalogReader` | Đọc catalog đang hiển thị (cây danh mục, tìm sản phẩm, PDP kèm variant) theo phạm vi brand. **Implemented** | Catalog |
| `VariantDirectory` | Tra variant (theo mã style, theo id) cho module khác. **Implemented** | Catalog |
| `ChannelDirectory` | Kênh bán của một brand. **Implemented** | Channel |
| `PriceResolver` | Giá hiệu lực của variant theo channel (nhóm khách: Designed). **Implemented** | Pricing |
| `InventoryReservation` | `reserve`, `release`, `commit` | Inventory |
| `InventoryAdjuster` | Điều chỉnh on-hand có lý do (movement) | Inventory |
| `AvailabilityReader` | ATS theo channel/location | Inventory |
| `OrderReader` | Đọc đơn (DTO snapshot) | Ordering |
| `OrderTransitions` | Yêu cầu chuyển trạng thái qua state machine | Ordering |
| `PaymentRecorder` | Ghi transaction, hoàn tiền | Payment |
| `ShipmentRecorder` | Tạo/cập nhật shipment và trạng thái vận đơn | Fulfillment |
| `CustomerDirectory` | Tìm/tạo khách theo SĐT, đọc consent | Customer |
| `SettingsRepository` | Đọc/ghi cấu hình theo scope | Tenancy |
| `IntegrationOutbox` | Đưa message ra ngoài có đảm bảo | Integration |
| `CurrentContext` | Brand/channel/locale/actor hiện tại; `runAs()` | Shared |
| `Authorizer` | Kiểm tra quyền theo scope | Identity |

## 3. Domain Events

Dispatch **sau commit**. Payload là DTO bất biến trong `Modules\<Ctx>\Events`.

| Context | Events |
|---|---|
| Catalog | `ProductCreated`, `ProductUpdated`, `ProductArchived`, `VariantCreated` (**Implemented**, `Modules\Catalog\Events`, sau commit) |
| Pricing | `PriceChanged` (**Implemented**) |
| Inventory | `StockReserved`, `StockReleased`, `StockCommitted`, `StockAdjusted`, `AvailabilityChanged` |
| Customer | `CustomerRegistered`, `CustomerMerged`, `ConsentChanged` |
| Cart | `CartUpdated`, `CartAbandoned` |
| Ordering | `OrderPlaced`, `OrderConfirmed`, `OrderCancelled`, `OrderCompleted` |
| Payment | `PaymentAuthorized`, `PaymentCaptured`, `PaymentFailed`, `RefundCreated`, `RefundCompleted` |
| Fulfillment | `ShipmentCreated`, `ShipmentStatusChanged`, `ShipmentDelivered` |
| Returns | `ReturnRequested`, `ReturnResolved` |
| Integration | `IntegrationMessageFailed`, `IntegrationMessageDead` |
| Extension | `PluginEnabled`, `PluginDisabled` |

## 4. Hooks public

| Hook | Loại | Chạy trong transaction | Mục đích |
|---|---|---|---|
| `vani.catalog.listing.query` | filter | không | Sửa truy vấn danh sách sản phẩm (merchandising) |
| `vani.catalog.product.view_data` | filter | không | Bổ sung dữ liệu hiển thị PDP |
| `vani.product.before_save` | validate | không (chạy trước transaction) | Chặn khi lưu sản phẩm (quy tắc riêng của brand); tham số `ProductDraft`. **Implemented** |
| `vani.product.after_save` | action | có (chỉ ghi DB) | Plugin lưu dữ liệu mở rộng của sản phẩm; tham số `(styleId, brandId)`. **Implemented** |
| `vani.checkout.payment_methods` | filter | không | Ẩn/hiện phương thức thanh toán |
| `vani.checkout.shipping_options` | filter | không | Sửa danh sách phương thức giao |
| `vani.checkout.before_validate` | validate | không | Kiểm tra bổ sung trước khi tính tổng |
| `vani.checkout.after_validate` | validate | không | Kiểm tra dựa trên tổng đã tính |
| `vani.order.before_create` | filter | có | Bổ sung `meta` cho đơn trước khi lưu (không sửa giá/dòng) |
| `vani.order.after_create` | action | có (chỉ ghi DB) | Plugin ghi dữ liệu gắn với đơn (usage voucher, attribution) |
| `vani.integration.order_payload` | filter | không | Bổ sung payload canonical gửi đối tác |
| `vani.storefront.pdp.after_price` | slot | — | UI dưới giá |
| `vani.storefront.checkout.before_submit` | slot | — | UI trước nút đặt hàng |
| `vani.admin.order.sidebar` | slot | — | Panel trên trang đơn Admin |

## 5. Registry (qua `PluginServiceProvider`)

| Registry | Ví dụ |
|---|---|
| `adminMenu()` | Mục menu Admin + permission — Implemented |
| `adminPages($namespace, $path)` | Trang Inertia của plugin (`'Promotion::Rules/Index'`) — Implemented |
| `permissions()` | Khai báo permission — Implemented (gán role mẫu: Designed) |
| `settingsSchema()` | JSON Schema cấu hình theo scope → form tự sinh; secret được mã hoá |
| `storefrontRoutes()` | Storefront API dưới `/api/storefront/v1/…` (được mở rộng tài nguyên Core, không ghi đè route Core) |
| `adminApiRoutes()` | `/api/admin/v1/plugins/{code}/…` |
| `webhookRoutes()` | `/api/integrations/{slug}/…` — Implemented |
| `adminRoutes()` | `/{VANI_ADMIN_PATH}/plugins/{slug}/…` (Inertia): Implemented |
| `scheduledTasks()` | Tác vụ định kỳ |
| `notificationTemplates()` | Mẫu tin theo event |
| `orderActions()` | Nút thao tác trên trang đơn Admin |
| `customerProfileTabs()` | Tab trên hồ sơ khách |
| `checkoutFields()` | Trường bổ sung ở checkout → lưu vào `orders.meta.<plugin>` |
| `integrationMessageTypes()` | Loại message tích hợp mới + JSON Schema |

## 6. Dữ liệu của plugin

- Bảng riêng `plg_<plugin>_*`, tham chiếu ID của Core bằng FK (`ON DELETE RESTRICT`). Core **không** FK sang bảng plugin.
- Cột `meta` (JSON) trên `orders`, `order_lines`, `carts`, `customers`, `styles`, `variants` dùng cho dữ liệu nhỏ, **namespace theo mã plugin** (`meta.einvoice.tax_code`). Không dùng cho dữ liệu cần lọc hoặc báo cáo.
- Plugin **không** thêm cột vào bảng Core, không sửa migration Core.
