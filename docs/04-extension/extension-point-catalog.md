# Danh mục Extension Points (public API v1)

> Trạng thái: **Designed**. Bảng này là **nguồn duy nhất** liệt kê extension point public. Thêm/đổi phải theo [compatibility policy](extension-model.md).

## 1. Contract: capability thay thế được

Plugin đăng ký bằng tag container. Registry của Core lọc theo trạng thái bật của plugin trong scope hiện tại (brand/channel/legal entity).

| Contract | Tag | Context | Mặc định trong Core | Tài liệu |
|---|---|---|---|---|
| `PaymentGateway` | `vani.payment.gateways` | Payment | `cod`, `manual_bank_transfer`. **Implemented** + bộ contract test `PaymentGatewayContract` | [payment](../10-payment/payment.md) |
| `ShippingCarrier` | `vani.shipping.carriers` | Fulfillment | `flat_rate`, `manual` | [fulfillment](../09-order/fulfillment.md) |
| `FulfillmentMethod` | `vani.fulfillment.methods` | Fulfillment | `delivery` | [fulfillment](../09-order/fulfillment.md) |
| `SourcingStrategy` | `vani.fulfillment.sourcing` | Fulfillment | `priority_first_fit` | [fulfillment](../09-order/fulfillment.md) |
| `InventoryStrategy` | `vani.inventory.strategies` | Inventory | `standard` (ATS = on_hand − reserved − safety). **Implemented**; strategy chỉ giảm được ATS (Core kẹp `min(strategy, standard)`) | [inventory](../08-inventory/inventory.md) |
| `PricingStrategy` | `vani.pricing.strategies` | Pricing | `price_list_priority`: **Implemented** (chọn bằng `VANI_PRICING_STRATEGY`) | [catalog-pricing](../03-domains/catalog-pricing.md) |
| `TaxCalculator` | `vani.tax.calculators` | Checkout | `vn_vat_inclusive` (**Implemented**, chọn bằng `VANI_TAX_CALCULATOR`) | [cart-checkout](../03-domains/cart-checkout.md) |
| `TotalsCalculator` | `vani.totals.calculators` | Checkout | subtotal (100), promotion (200), shipping (500), tax (800), guard (900); plugin dùng 300–399. **Implemented** | [cart-checkout](../03-domains/cart-checkout.md) |
| `CheckoutValidator` | `vani.checkout.validators` | Checkout | `core` (giỏ, một brand, liên hệ, địa chỉ, giao hàng, thanh toán). **Implemented** | [cart-checkout](../03-domains/cart-checkout.md) |
| `ShippingRateProvider` | `vani.checkout.shipping_providers` | Checkout | `FlatRateShipping` (**Implemented**; carrier thật: slice 9) | [cart-checkout](../03-domains/cart-checkout.md) |
| `PromotionRule` | `vani.promotion.rules` | Promotion | — (rule do plugin cung cấp). **Implemented** (rule chưa đăng ký → khuyến mãi bị bỏ qua + log) | [promotion](../03-domains/promotion.md) |
| `PromotionAction` | `vani.promotion.actions` | Promotion | `percent_off`, `amount_off` (primitive). **Implemented** | [promotion](../03-domains/promotion.md) |
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
| `InventoryReservation` | `reserve`, `release`, `commit` (**Implemented**) | Inventory |
| `InventoryAdjuster` | Điều chỉnh on-hand có lý do (movement) — hiện là service nội bộ `StockAdjustmentService`, chưa công bố contract (chờ Integration) | Inventory |
| `AvailabilityReader` | ATS theo channel (**Implemented**; theo location: chưa) | Inventory |
| `Carts` | Giỏ: tạo, xem, thêm/sửa/xoá dòng, gộp, khoá cho checkout. **Implemented** | Cart |
| `PromotionEngine` | Đánh giá khuyến mãi, ghi nhận/hoàn lượt. **Implemented** | Promotion |
| `Checkout` | `quote`, `placeOrder` (idempotent). **Implemented** | Checkout |
| `OrderWriter` | Tạo đơn từ bản nháp đã tính (trong transaction PlaceOrder). **Implemented** | Ordering |
| `OrderReader` | Đọc đơn (DTO snapshot). **Implemented** | Ordering |
| `OrderTransitions` | Chuyển trạng thái qua state machine, cập nhật `payment_status`. **Implemented** | Ordering |
| `Payments` | Phương thức khả dụng, tạo/khởi tạo payment, xác nhận thủ công, hoàn tiền (thay `PaymentRecorder` trong thiết kế). **Implemented** | Payment |
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
| Inventory | `StockReserved`, `StockReleased`, `StockCommitted`, `StockAdjusted`, `AvailabilityChanged` (**Implemented**) |
| Customer | `CustomerRegistered`, `CustomerMerged`, `ConsentChanged` |
| Cart | `CartUpdated` (**Implemented**), `CartAbandoned` |
| Ordering | `OrderPlaced`, `OrderConfirmed`, `OrderCancelled` (**Implemented**), `OrderCompleted` |
| Payment | `PaymentCaptured`, `PaymentFailed`, `RefundCreated`, `RefundCompleted` (**Implemented**), `PaymentAuthorized` |
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
| `vani.cart.validate_line` | validate | có (khoá giỏ; không I/O mạng) | Chặn thêm/tăng dòng giỏ (giới hạn mua mỗi khách, hàng chỉ bán tại cửa hàng…); tham số `CartLineDraft` (số lượng sau thay đổi). **Implemented** |
| `vani.checkout.payment_methods` | filter | có (khi đặt hàng) | Ẩn/hiện phương thức thanh toán; tham số `(methods, Totals)`. **Implemented** |
| `vani.checkout.shipping_options` | filter | có (khi đặt hàng) | Sửa danh sách phương thức giao; tham số `(options, TotalsContext)`. **Implemented** |
| `vani.checkout.before_validate` | validate | có (transaction đặt hàng; không I/O mạng) | Kiểm tra bổ sung trước validator Core; tham số `CheckoutRequest`. **Implemented** |
| `vani.checkout.after_validate` | validate | có | Kiểm tra dựa trên tổng đã tính; tham số `(CheckoutRequest, Totals)`. **Implemented** |
| `vani.order.before_create` | filter | có | Bổ sung `meta` cho đơn trước khi lưu (không sửa giá/dòng) |
| `vani.order.after_create` | action | có (chỉ ghi DB) | Plugin ghi dữ liệu gắn với đơn (attribution, điểm chờ); tham số `PlacedOrder`. **Implemented** |
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
