# Danh mục Extension Points (public API v1)

> Trạng thái: **Partially Implemented**. Đây là danh mục extension point public hiện tại; mục nào chưa triển khai được ghi rõ. Thêm/đổi phải theo [compatibility policy](extension-model.md).
>
> Plugin được bật/tắt ở phạm vi toàn cửa hàng ([ADR-028](../19-adr/ADR-028-single-store-brand-as-catalog.md)). Brand là dữ liệu Catalog, không phải scope plugin; các thay đổi contract của slice 12 được ghi trong [CHANGELOG-extension](CHANGELOG-extension.md).

## 1. Contract: capability thay thế được

Plugin đăng ký bằng `contribute(<Contract>::TAG, Implementation::class)` — **mọi tag là hằng `TAG` trên interface trong `Contracts`** (arch test chặn định nghĩa tag ở tầng khác; hằng cũ trong `Application` còn làm alias `@deprecated`). Registry của Core chỉ đưa implementation của plugin đang bật vào hiệu lực.

| Contract | Tag | Context | Mặc định trong Core | Tài liệu |
|---|---|---|---|---|
| `PaymentGateway` | `vani.payment.gateways` | Payment | `cod`, `manual_bank_transfer`. **Implemented** + bộ contract test `PaymentGatewayContract`. Interface tuỳ chọn: `CapturesLater` (0.3.5), `CallbackResponder` (0.3.15: phản hồi IPN theo `CallbackOutcome`, tham chiếu `vani.vnpay`) | [payment](../10-payment/payment.md) |
| `OrderStatusAction` | `vani.ordering.status_actions` | Ordering | — (plugin). **Implemented** (0.3.98): thao tác đổi trạng thái do plugin thanh toán/vận chuyển cắm vào thẻ "Trạng thái đơn" trên trang đơn Admin — `describe(OrderData)` → `OrderStatusActionView` (nhãn, quyền, ô nhập `FieldDefinition`, xác nhận, `primary`) hoặc null; `perform(OrderData, input)` đổi trạng thái qua contract Core (R30). Core hiện nút, kiểm quyền, validate, ghi audit `order.status_action`; lỗi nghiệp vụ hiện cho nhân viên, lỗi khác báo chung | [order](../09-order/order.md) |
| `ShippingCarrier` | `vani.shipping.carriers` | Fulfillment | `manual`. **Implemented** + bộ contract test `ShippingCarrierContract` (phí ở checkout: `ShippingRateProvider`) | [fulfillment](../09-order/fulfillment.md) |
| `FulfillmentMethod` | `vani.fulfillment.methods` | Fulfillment | `delivery` | [fulfillment](../09-order/fulfillment.md) |
| `SourcingStrategy` | `vani.fulfillment.sourcing` | Fulfillment | `reserved_locations` (**Implemented**; đề xuất phải khớp hàng đang giữ) | [fulfillment](../09-order/fulfillment.md) |
| `InventoryStrategy` | `vani.inventory.strategies` | Inventory | `standard` (ATS = on_hand − reserved − safety). **Implemented**; strategy chỉ giảm được ATS (Core kẹp `min(strategy, standard)`) | [inventory](../08-inventory/inventory.md) |
| `StockPageLink` | `vani.inventory.stock_links` | Inventory | — (plugin); `vani.warehouse` thêm liên kết chuyển kho và quản lý kho (0.3.88) | [inventory](../08-inventory/inventory.md) |
| `PricingStrategy` | `vani.pricing.strategies` | Pricing | `price_list_priority`: **Implemented** (chọn bằng `VANI_PRICING_STRATEGY`) | [catalog-pricing](../03-domains/catalog-pricing.md) |
| `TaxCalculator` | `vani.tax.calculators` | Checkout | Core: `none`; `vn_vat_inclusive` là plugin hệ thống `vani.tax-vn-vat` (**Implemented**, chọn bằng `core.tax.calculator`/`VANI_TAX_CALCULATOR`). Bắt buộc đúng 1 | [cart-checkout](../03-domains/cart-checkout.md) |
| `PhoneNumberPolicy` | `vani.phone.policies` | Shared (gắn ở composition root) | Core: `international`; `vn` là plugin hệ thống `vani.phone-vn` (**Implemented 0.3.42**, chọn bằng `vanishop.locale.phone_policy`/`VANI_PHONE_POLICY`). Bắt buộc đúng 1; gọi qua `Shared\Support\Phones` | [commerce-kernel §3.2](../02-architecture/commerce-kernel.md) |
| `TotalsCalculator` | `vani.totals.calculators` | Checkout | subtotal (100), promotion (200), shipping (500), tax (800), guard (900); plugin dùng 300–399. **Implemented** | [cart-checkout](../03-domains/cart-checkout.md) |
| `CheckoutValidator` | `vani.checkout.validators` | Checkout | `core` (giỏ, liên hệ, địa chỉ, giao hàng, thanh toán; `no_payment_method` khi không cổng nào khả dụng). **Implemented** | [cart-checkout](../03-domains/cart-checkout.md) |
| `AddressDirectory` | `vani.checkout.address_directories` | Checkout | — (plugin hệ thống `vani.provinces-vn`). **Implemented** (0.3.11): kiểm tra + chuẩn hoá địa chỉ giao hàng, API danh mục, chọn tỉnh/phường ở checkout native | [cart-checkout](../03-domains/cart-checkout.md) |
| `ShippingRateProvider` | `vani.checkout.shipping_providers` | Checkout | Không có trong Core; phí cố định là plugin hệ thống `vani.shipping-flat-rate`, GHN là plugin (**Implemented**). Bắt buộc ≥ 1 | [cart-checkout](../03-domains/cart-checkout.md) |
| `PromotionRule` | `vani.promotion.rules` | Promotion | — (rule do plugin cung cấp). **Implemented** (rule chưa đăng ký → khuyến mãi bị bỏ qua + log). Rule chỉ phụ thuộc dòng hàng nên `implements CartContentRule` (0.3.31) để được kiểm tra lại khi khách bớt hàng | [promotion](../03-domains/promotion.md) |
| `PromotionAction` | `vani.promotion.actions` | Promotion | `percent_off`, `amount_off` (primitive). **Implemented** | [promotion](../03-domains/promotion.md) |
| `ReturnPolicy` | `vani.returns.policies` | Returns | `days_window` (**Implemented**, chọn bằng `VANI_RETURN_POLICY`) | [order §7](../09-order/order.md) |
| `SearchProvider` | `vani.search.providers` | Catalog | `database` (**Implemented**); `meilisearch` là plugin `vani.search-meilisearch`; interface tuỳ chọn `ConfigurableSearchIndex` (`setupIndex()` cho `--setup`) | [catalog-pricing](../03-domains/catalog-pricing.md) |
| `ImageSizeProvider` | `vani.catalog.image_sizes` | Catalog | Không có mặc định; `vani.lookbook` khai báo `lookbook.cover` (0.3.87). Nhân viên ghi đè ở Admin → Thư viện ảnh → Kích thước ảnh | [storefront](../14-storefront/storefront.md) |
| `ImageFormat` | `catalog.image-format` | Catalog | Không có mặc định (giữ định dạng gốc); `webp` là plugin `vani.media-webp` (0.3.28) | [storefront](../14-storefront/storefront.md) |
| `VideoDriver` | `catalog.video-driver` | Catalog | Không có mặc định (không thêm được video); `youtube` là plugin `vani.video-youtube` (0.3.46, ADR-035). Driver trả URL nhúng, theme tự dựng iframe | [ADR-035](../19-adr/ADR-035-product-links-videos-from-vanilo.md) |
| `OtpSender` | `vani.customer.otp_senders` | Customer | `email`, `log` (chỉ dev). **Implemented**: thử theo `priority()` giảm dần; kênh ném `OtpDeliveryFailed` → kênh kế tiếp. Plugin: `vani.zalo-zns` (60), `vani.sms-brandname` (50) | [customer](../03-domains/customer.md) |
| `AuthProvider` | `vani.customer.auth_providers` | Customer | — (plugin). **Implemented** (0.3.7): Core lo `state`, danh sách `redirect_uri`, ghép danh tính (đã liên kết → SĐT đã xác minh → email đã xác minh của khách đang có), token; contract test `AuthProviderContract`. Chưa có plugin thật (dự kiến `vani.social-login`) | [extension-surface-v2 §4.E](extension-surface-v2.md) |
| `NotificationChannel` | `vani.notification.channels` | Notification | `mail`. **Implemented**; plugin: `sms` (`vani.sms-brandname`), `zns` (`vani.zalo-zns`) | [notification](../03-domains/notification.md) |
| `Connector` | `vani.integration.connectors` | Integration | — (connector là plugin). **Implemented** (slice 11): nhận message outbox theo `supports()`, trả `DeliveryResult` ok/retryable/permanent | [integration-platform](../11-integration/integration-platform.md) |
| `InboundHandler` | `vani.integration.inbound` | Integration | —. **Implemented** (slice 11): xử lý message inbox theo `(system, message_type)`, trả `DeliveryResult` (thêm `stale`) | [integration-platform](../11-integration/integration-platform.md) |
| `ErpConnector` (extends `Connector`) | `vani.integration.erp` | Integration | — | [erp-integration](../11-integration/erp-integration.md) |
| `CartLineOption` | `vani.cart.line_options` | Cart | — (plugin). **Implemented** (0.3.4): tuỳ chọn dòng giỏ theo plugin id, chuẩn hoá + từ chối, chụp sang dòng đơn; không đổi giá (phụ thu: Designed); tham chiếu `vani.hello-world` (lời chúc gói quà) | [extension-surface-v2 §4.C](extension-surface-v2.md) |
| `PluginHealthCheck` | `vani.health.checks` | Extension | — (plugin). **Implemented** (0.3.6): `vani:plugin:health` + doctor + trang Admin Plugin; tham chiếu `vani.sms-brandname`, `vani.zalo-zns` (thiếu khoá, sandbox) | [extension-surface-v2 §4.G](extension-surface-v2.md) |
| `StorefrontEnricher` | `vani.storefront.enrichers` | Storefront | — (plugin). **Implemented** (0.3.1): làm giàu `product_card`/`product`/`cart`/`order` dưới `extensions.<plugin-id>`, batch, lỗi bị bỏ; contract test `StorefrontEnricherContract`; tham chiếu `vani.hello-world` | [extension-surface-v2 §4.B](extension-surface-v2.md) |
| `LinkType` | `vani.storefront.link_types` | Storefront | `category`, `collection`, `brand`, `product`, `route`, `url`; plugin `vani.cms`: `page`, `article`. **Implemented** (0.3.58): link có kiểu cho menu/khối, phân giải theo lô (`resolve(values, locale)`), tìm cho Admin (`search`), kiểm dạng (`accepts`); contract test `LinkTypeContract` ([ADR-040](../19-adr/ADR-040-typed-links-menus.md)) | [storefront §4a](../14-storefront/storefront.md) |
| `StorefrontBlock` | `vani.storefront.blocks` | Storefront | `hero`, `product_grid`, `brand_grid`, `rich_text`. **Implemented** (0.3.10): page builder trang chủ (Admin → Giao diện), contract test `StorefrontBlockContract`, tham chiếu `vani.hello-world` | [storefront §4](../14-storefront/storefront.md) |
| `SitemapProvider` | `vani.storefront.sitemap` | Storefront | — (plugin). **Implemented** (0.3.14): URL công khai của plugin vào `/sitemap.xml`, lỗi bị bỏ; tham chiếu `vani.cms` | [storefront §5](../14-storefront/storefront.md) |
| `DashboardWidget` | `vani.admin.dashboard.widgets` | Extension | — (plugin). **Implemented** (0.3.12): ô Tổng quan trả `Metric`/`Series`/`Table`, Core lọc quyền + cô lập lỗi; tham chiếu `vani.reports` | [extension-surface-v2 §4.G](extension-surface-v2.md) |
| `ReportProvider` | `vani.admin.reports` | Extension | — (plugin). **Implemented** (0.3.12): Admin → Báo cáo, `run(ReportPeriod): ReportResult`, Core dựng trang + CSV; tham chiếu `vani.reports` | [extension-surface-v2 §4.G](extension-surface-v2.md) |

Extension contract không có abstract base: mở rộng bằng field tuỳ chọn hoặc interface bổ sung tuỳ chọn ([extension-model §5](extension-model.md)); mọi thay đổi ghi ở [CHANGELOG-extension](CHANGELOG-extension.md). Mỗi extension contract có bộ **contract test** trong `Modules\<Ctx>\Testing` mà plugin phải chạy ([testing §6](../17-testing/testing.md)).

## 2. Service contract: plugin gọi Core

| Contract | Chức năng | Context |
|---|---|---|
| `CatalogReader` | Đọc catalog đang hiển thị (cây danh mục, tìm sản phẩm, PDP kèm variant, danh sách/chi tiết brand). **Implemented** | Catalog |
| `VariantDirectory` | Tra variant (theo mã style, theo id) cho module khác. **Implemented** | Catalog |
| `CatalogImporter` | Nhập sản phẩm (`upsertProduct(ProductImport)`): idempotent theo mã style, tạo thương hiệu/danh mục/màu/size còn thiếu, ảnh từ file local qua thư viện media, sinh variant; không ghi đè nội dung đã sửa. **Implemented** (0.3.27), dùng bởi `database/seeders/Demo/CatalogSeeder` (trước: plugin `vani.demo-catalog`, đã gỡ 2026-11-05) | Catalog |
| `PriceResolver` | Giải giá hiệu lực theo variant và `PricingContext` (nhóm khách/khách hàng nếu có). **Implemented** | Pricing |
| `PriceImporter` | Đặt giá niêm yết (bảng `base`) theo SKU. **Implemented** (0.3.27) | Pricing |
| `InventoryReservation` | `reserve`, `release`, `commit` (**Implemented**) | Inventory |
| `LocationRegistry` | Đọc/tạo/sửa kho (`LocationData`), audit + optimistic lock, mã trùng → ValidationException. **Implemented** (0.3.88), dùng bởi `vani.warehouse` | Catalog/Inventory |
| `StockMovements` | `transferOut`/`transferIn` tại một kho do VaniShop quản lý: khoá dòng tồn, sổ biến động, `AvailabilityChanged`; chạy trong transaction của bên gọi; từ chối → `StockMovementRejected`. **Implemented** (0.3.88), dùng bởi `vani.warehouse` | Inventory |
| `InventoryReconciliation` | `reconcileExternal(source, rows, apply)` — đối soát snapshot nguồn ngoài vào bảng đối soát chung. **Implemented** (0.3.88), dùng bởi `vani.warehouse` | Inventory |
| `StockImporter` | Đặt tồn tuyệt đối theo SKU tại kho do VaniShop quản lý (movement `sync` + audit; kho authority ngoài dùng `InventorySync`). **Implemented** (0.3.27) | Inventory |
| `InventoryAdjuster` | Điều chỉnh on-hand có lý do (movement) — hiện là service nội bộ `StockAdjustmentService`, chưa công bố contract (chờ Integration) | Inventory |
| `AvailabilityReader` | Đọc ATS theo variant; không nhận channel hoặc location (**Implemented**) | Inventory |
| `Carts` | Giỏ: tạo, xem, thêm/sửa/xoá dòng, gộp, khoá cho checkout. **Implemented** | Cart |
| `PromotionEngine` | Đánh giá khuyến mãi, ghi nhận/hoàn lượt. **Implemented** | Promotion |
| `Checkout` | `quote`, `placeOrder` (idempotent). **Implemented** | Checkout |
| `ShippingAddresses` | Địa chỉ giao hàng theo danh mục địa giới đang dùng (`directory()`, `normalize()`): cùng quy tắc cho checkout, sổ địa chỉ khách, Admin sửa địa chỉ đơn. **Implemented** (0.3.13) | Checkout |
| `OrderWriter` | Tạo đơn từ bản nháp đã tính (trong transaction PlaceOrder). **Implemented** | Ordering |
| `OrderReader` | Đọc đơn (DTO snapshot). **Implemented** | Ordering |
| `OrderStatistics` | Số liệu bán hàng tổng hợp: tổng, theo ngày (múi giờ), theo phương thức thanh toán/nguồn đơn/sản phẩm/thương hiệu, đếm theo trạng thái. **Implemented** (0.3.12) | Ordering |
| `CustomerOrders` | Tra cứu/xem/huỷ đơn cho khách vãng lai. **Implemented** | Ordering |
| `OrderTransitions` | Chuyển trạng thái qua state machine, cập nhật `payment_status`. **Implemented** | Ordering |
| `Payments` | Phương thức khả dụng, tạo/khởi tạo payment, xác nhận thủ công, hoàn tiền (thay `PaymentRecorder` trong thiết kế). **Implemented** | Payment |
| `ShipmentReader` | Vận đơn của một đơn (Storefront, panel Admin). **Implemented**. Ghi nhận trạng thái đi qua webhook chung/Admin, chưa công bố `ShipmentRecorder` | Fulfillment |
| `InventoryReturns` | Nhập lại hàng về kho (movement `return`). **Implemented** | Inventory |
| `Returns` | Tạo/xem/huỷ yêu cầu đổi/trả, số lượng còn trả được. **Implemented** | Returns |
| `CustomerDirectory` | Tìm/tạo khách theo SĐT, đọc consent | Customer |
| `PageSeo` | Trang storefront khai báo URL tương ứng theo ngôn ngữ + ngôn ngữ đã dịch (`alternates`), hoặc `noindex()`; layout sinh canonical/hreflang/robots/nút chuyển ngôn ngữ. **Implemented** (0.3.61, ADR-038) | Storefront |
| `LinkResolver` | Phân giải `LinkTarget` → `ResolvedLink {url, label, exists}` theo ngôn ngữ, theo lô; loại đang có, tìm đối tượng. **Implemented** (0.3.58) | Storefront |
| `CatalogLookup` | Đối tượng catalog đang hiển thị theo id (`category`, `collection`, `brand`, `product`), một truy vấn mỗi loại; tìm cho Admin. **Implemented** (0.3.58) | Catalog |
| `CustomerAccounts` | Khách tự quản lý hồ sơ, mật khẩu, sổ địa chỉ, xoá tài khoản (`deletionBlockers`, `requestDeletionOtp`, `deleteAccount` — 0.3.57) (bề mặt ngoài Storefront API, vd. native storefront). **Implemented** (0.3.13) | Customer |
| `Settings` | Đọc/ghi cấu hình và khai báo định nghĩa; cấu hình ở một cấp cửa hàng. **Implemented** | Tenancy |
| `IntegrationOutbox` | Đưa message ra ngoài có đảm bảo | Integration |
| `CurrentContext` | Actor/locale hiện tại; `runAs()` tạm thời đổi context | Shared |
| `Authorizer` | Kiểm tra quyền ở phạm vi `owner` hoặc `location` | Identity |

## 3. Domain Events

Dispatch **sau commit**. Payload là DTO bất biến trong `Modules\<Ctx>\Events`. Plugin nghe qua `PluginServiceProvider::onEvent()`; listener chỉ chạy khi plugin tương ứng đang bật. Việc bật plugin không lọc event theo brand hay channel.

| Context | Events |
|---|---|
| Catalog | `ProductCreated`, `ProductUpdated`, `ProductArchived`, `VariantCreated` (**Implemented**, `Modules\Catalog\Events`, sau commit) |
| Pricing | `PriceChanged` (**Implemented**) |
| Inventory | `StockReserved`, `StockReleased`, `StockCommitted`, `StockAdjusted`, `AvailabilityChanged` (**Implemented**) |
| Customer | `CustomerRegistered`, `CustomerMerged`, `ConsentChanged` |
| Cart | `CartUpdated`, `CartAbandoned` (**Implemented**; `CartAbandoned` từ 0.3.3, giỏ của khách không hoạt động quá ngưỡng) |
| Ordering | `OrderPlaced`, `OrderConfirmed`, `OrderCancelled`, `OrderCompleted` (**Implemented**; `OrderCompleted` từ 0.3.1, khi hết hạn đổi trả sau giao), `OrderStatusOverridden` (0.3.99: đổi trạng thái ngoài trình tự) |
| Payment | `PaymentCaptured`, `PaymentFailed`, `RefundCreated`, `RefundCompleted`, `PaymentAuthorized` (**Implemented**; `PaymentAuthorized` từ 0.3.5 — cổng `CapturesLater`) |
| Fulfillment | `ShipmentCreated`, `ShipmentStatusChanged` (**Implemented**; giao thành công = `ShipmentStatusChanged` với `to = delivered`) |
| Returns | `ReturnRequested`, `ReturnResolved` (**Implemented**) |
| Integration | `IntegrationMessageFailed`, `IntegrationMessageDead` |
| Extension | `PluginEnabled`, `PluginDisabled` |

## 4. Hooks public

| Hook | Loại | Chạy trong transaction | Mục đích |
|---|---|---|---|
| `vani.catalog.listing.query` | filter | không | Sửa truy vấn danh sách sản phẩm (merchandising); tham số/kết quả `ProductSearchQuery`, sai kiểu bị bỏ qua. **Implemented** (0.3) |
| `vani.catalog.product.view_data` | filter | không | Bổ sung dữ liệu hiển thị PDP |
| `vani.customer.before_delete` | validate | không (trước khi kiểm OTP; chỉ đọc) | Việc còn dở chặn khách tự xoá tài khoản; trả `list<{type, reference, label}>`, có mục → `customer.deletion_blocked`. Core: Ordering báo đơn chưa hoàn tất / đổi trả đang mở. Nhân viên ẩn danh hoá trong Admin không qua hook. **Implemented** (0.3.57) |
| `vani.product.before_save` | validate | không (chạy trước transaction) | Chặn khi lưu sản phẩm (quy tắc riêng của cửa hàng); tham số `ProductDraft`. **Implemented** |
| `vani.product.after_save` | action | có (chỉ ghi DB) | Plugin lưu dữ liệu mở rộng của sản phẩm; tham số `styleId`. **Implemented** |
| `vani.cart.validate_line` | validate | có (khoá giỏ; không I/O mạng) | Chặn thêm/tăng dòng giỏ (giới hạn mua mỗi khách, hàng chỉ bán tại cửa hàng…); tham số `CartLineDraft` (số lượng sau thay đổi). **Implemented** |
| `vani.checkout.payment_methods` | filter | có (khi đặt hàng) | Ẩn/hiện phương thức thanh toán; tham số `(methods, Totals)`. **Implemented** |
| `vani.checkout.shipping_options` | filter | có (khi đặt hàng) | Sửa danh sách phương thức giao; tham số `(options, TotalsContext)`. **Implemented** |
| `vani.checkout.before_validate` | validate | có (transaction đặt hàng; không I/O mạng) | Kiểm tra bổ sung trước validator Core; tham số `CheckoutRequest`. **Implemented** |
| `vani.checkout.after_validate` | validate | có | Kiểm tra dựa trên tổng đã tính; tham số `(CheckoutRequest, Totals)`. **Implemented** |
| `vani.checkout.context` | filter | có (khi đặt hàng; không I/O mạng) | Bổ sung `PromotionContext::$attributes` từ request (mã giới thiệu, chiến dịch). **Implemented** (0.3.4) |
| `vani.order.before_create` | filter | có (không I/O mạng) | Bổ sung `orders.meta` (khoá theo plugin id) trước khi lưu; tham số `(meta, CheckoutRequest, Totals)`; không sửa giá/dòng. Đọc lại qua `OrderData::$meta`. **Implemented** (0.3) |
| `vani.order.after_create` | action | có (chỉ ghi DB) | Plugin ghi dữ liệu gắn với đơn (attribution, điểm chờ); tham số `PlacedOrder`. **Implemented** |
| `vani.integration.order_payload` | filter | không | Bổ sung payload canonical gửi đối tác; tham số `(payload, OrderData)`; chỉ được **thêm** khoá. **Implemented** (0.3) |
| `vani.admin.dashboard.cards` | slot | — | Card trên dashboard Admin; trả `{title, body}`. **Implemented** (HelloWorld dùng) |
| `vani.admin.dashboard.tasks` | slot | 0.3.101 | Ô "Việc cần làm" đầu trang tổng quan; trả `list<{key, label, count, url, tone?, hint?, order?}>`. **Implemented** (Ordering, Returns, Fulfillment, Payment, Inventory) |
| `vani.admin.product.list_columns` | slot | 0.3.103 | Cột thêm ở danh sách sản phẩm Admin; tham số `list<int>` style id, trả `{key, label, values}` hoặc null. **Implemented** (Pricing: Giá bán; Inventory: Tồn bán được) |
| `vani.admin.product.bulk_actions` | slot | 0.3.103 | Thao tác hàng loạt ở danh sách sản phẩm; trả `list<{key, label, url, confirm, fields?, data?}>`. **Implemented** (Pricing: Đổi giá) |
| `vani.admin.order.sidebar` | slot | — | Panel trên trang đơn Admin; tham số `OrderDetail`, trả `{title, rows[{label, value}], link?, actions?}` — `actions` (0.3.96): `{key, label, url, data?, fields?, confirm?, tone?, primary?}`. **Implemented** (Payment, Fulfillment, Returns) |
| `vani.admin.order.list_columns` | slot | 0.3.96 | Cột thêm ở danh sách đơn Admin (module Core); tham số `list<int>` id đơn, trả `{key, label, values{id: string}}` hoặc null. **Implemented** (Fulfillment: Vận đơn) |
| `vani.admin.order.bulk_actions` | slot | 0.3.96 | Thao tác hàng loạt ở danh sách đơn Admin (module Core); trả `list<{key, label, url, confirm}>`, nút POST `{ids}`. **Implemented** (Fulfillment: Tạo vận đơn, Đã bàn giao) |
| `vani.admin.order.timeline` | slot | 0.3.96 | Sự kiện gộp vào lịch sử trang đơn Admin; tham số `OrderDetail`, trả `list<{ts, at, title, detail?, source?}>`. **Implemented** (Fulfillment, Payment) |
| `vani.admin.order.list_tools` | slot | 0.3.97 | Công cụ tải file ở đầu danh sách đơn Admin; trả `list<{key, label, url, accept?, help?, fields?}>` — POST multipart `file` + fields. **Implemented** (Fulfillment: nhập mã vận đơn) |
| `vani.admin.order.payment_methods` | slot | 0.3.96 | Nhãn cách thanh toán cho danh sách đơn; trả `array<mã, nhãn>`. **Implemented** (Payment) |

### 4.1 Slot storefront (Implemented, khai báo ở `modules/Storefront/hooks.php`)

Theo [ADR-025](../19-adr/ADR-025-native-storefront-ssr-slots.md): listener trả `Modules\Storefront\Contracts\Data\SlotView` (view của plugin + dữ liệu) hoặc `Htmlable`; theme render tại vị trí slot bằng `<x-vani::hook-slot>`, chỉ nối thêm, lỗi một listener (hoặc lỗi lúc render) bị bỏ qua, chuỗi thô bị bỏ. Các slot dưới đây là public API (`since 0.3`); `account.menu` là mục bổ sung cho menu tài khoản native, ngoài các mục do `accountPage()` đăng ký.

| Slot | Vị trí | Ví dụ plugin |
|---|---|---|
| `vani.storefront.layout.head` | Cuối `<head>` | Pixel tracking, meta xác minh |
| `vani.storefront.layout.body_start` | Đầu `<body>`, trước header (0.3.43); HTML có cache, không dùng dữ liệu riêng của khách | Thanh thông báo |
| `vani.storefront.layout.body_end` | Cuối `<body>` | Chat, script đo lường |
| `vani.storefront.plp.card_badges` | Nhãn trên thẻ sản phẩm ở danh sách | "Mới", "Bán chạy", "Freeship" |
| `vani.storefront.plp.empty` | Trạng thái danh sách không có sản phẩm; nhận context và listing | Gợi ý tìm kiếm, sản phẩm thay thế |
| `vani.storefront.brands.before_list` / `brands.after_list` | Trước điều hướng chữ cái / sau danh sách thương hiệu (0.3.105); args: brandGroups | Nội dung chiến dịch, liên kết bổ sung |
| `vani.storefront.pdp.before_gallery` | Trước thư viện ảnh; args: product (0.3.105) | Thông báo chiến dịch |
| `vani.storefront.pdp.after_title` | Dưới tên sản phẩm | Đánh giá sao |
| `vani.storefront.pdp.after_price` | Dưới giá | Trả góp, điểm thưởng dự kiến |
| `vani.storefront.pdp.before_add_to_cart` | Sau giá, trước form thêm giỏ; args: product (0.3.105) | Ưu đãi, thông tin giao hàng |
| `vani.storefront.pdp.after_add_to_cart` | Dưới nút thêm giỏ | Bảng size, cam kết đổi trả |
| `vani.storefront.pdp.after_details` | Sau mô tả | Lookbook, phối đồ, sản phẩm gợi ý |
| `vani.storefront.pdp.after_videos` / `pdp.after_related_products` | Sau video / sau nhóm sản phẩm liên quan; args: product (0.3.105) | Nội dung bổ sung, chiến dịch |
| `vani.storefront.cart.after_lines` | Sau danh sách dòng giỏ | Tiến độ freeship, upsell |
| `vani.storefront.checkout.after_shipping` | Sau chọn phương thức giao | Ghi chú giao hàng, gói quà |
| `vani.storefront.checkout.before_submit` | Trước nút đặt hàng | Xuất hoá đơn điện tử (trường `extra[<plugin id>]`) |
| `vani.storefront.order.after_summary` | Trang cảm ơn / chi tiết đơn | Hướng dẫn chuyển khoản, điểm đã cộng |
| `vani.storefront.header.nav` / `header.actions` | Menu chính / cụm thao tác cạnh giỏ (0.3.1) | Trang plugin; wishlist, điểm |
| `vani.storefront.footer.columns` | Cột footer (0.3.1) | Chính sách, đăng ký nhận tin |
| `vani.storefront.layout.header.before` / `header.after` | Ngay trước / sau header (0.3.105); nằm trong HTML có cache CDN | Banner, thông báo công khai |
| `vani.storefront.layout.main.before` / `main.after` | Đầu / cuối vùng nội dung mọi trang (0.3.105); HTML có thể được cache CDN | Khuyến mãi chung, ghi chú cửa hàng |
| `vani.storefront.layout.footer.after` | Sau footer mọi trang (0.3.105); nằm trong HTML có cache CDN | Dấu chứng nhận, nội dung liên hệ |
| `vani.storefront.home.before_blocks` / `home.after_blocks` | Trước / sau các block trang chủ, kể cả page builder (0.3.105) | Banner chiến dịch, nội dung plugin |
| `vani.storefront.plp.before_listing` | Đầu trang danh sách, trên bộ lọc và lưới (0.3.43); args: ngữ cảnh `{type: category\|brand\|search, category, brand}`, listing | Banner danh mục |
| `vani.storefront.plp.filters` | Bộ lọc bổ sung trang danh sách (0.3.1) | Lọc theo dữ liệu plugin |
| `vani.storefront.plp.card.actions` | Khu vực thao tác dưới mỗi thẻ sản phẩm, bên ngoài link PDP (0.3.105); args: product | Yêu thích, so sánh |
| `vani.storefront.plp.after_listing` | Sau bộ lọc, lưới và phân trang (0.3.105); args: context, listing | Banner cuối danh sách, điều khoản |
| `vani.storefront.pdp.gallery_after` | Dưới ảnh sản phẩm (0.3.1) | Video, 360° |
| `vani.storefront.cart.empty` | Sau thông báo giỏ hàng trống (0.3.105) | Gợi ý sản phẩm, liên kết mua sắm |
| `vani.storefront.cart.before_lines` / `cart.after_summary` | Trước danh sách dòng / sau tổng kết giỏ (0.3.105); args: cart | Thông báo, ưu đãi |
| `vani.storefront.checkout.order_summary` / `checkout.after_summary` | Trong / sau tóm tắt đơn ở checkout (0.3.105); args: quote | Thông tin giao dịch, thông báo khuyến mãi |
| `vani.storefront.checkout.contact_before` | Trước phần thông tin người nhận (0.3.105); args: quote | Thanh tiến độ, thông báo checkout |
| `vani.storefront.checkout.contact_after` / `address_after` / `payment_after` | Sau từng bước checkout (0.3.1); trường `extra[<plugin id>]` | Mã số thuế HĐĐT, ghi chú giao, ưu đãi theo cổng |
| `vani.storefront.account.menu` / `account.dashboard` / `account.order_detail` | Menu, tổng quan, chi tiết đơn trong `/tai-khoan` (0.3.9); mục menu có trang riêng: `accountPage()` | Điểm thưởng, ví, hoá đơn |
| `vani.storefront.account.orders` | Sau danh sách đơn ở trang tổng quan hoặc tất cả đơn (0.3.105); args: orders | Điểm thưởng, thông tin thành viên |
| `vani.storefront.account.addresses` / `account.profile` / `account.login` | Sau nội dung tương ứng trong tài khoản (0.3.105); profile nhận customer | Hướng dẫn, nội dung plugin |
| `vani.storefront.track.after_order` | Sau thông tin đơn tìm thấy ở trang tra cứu (0.3.105); args: order | Trạng thái vận đơn, hỗ trợ |
| `vani.storefront.order.before_summary` | Trước tóm tắt đơn ở trang cảm ơn hoặc chi tiết đơn tài khoản (0.3.105); args: order | Thông tin giao dịch, hướng dẫn |

Các slot `layout.*` chạy trên HTML storefront có thể được cache CDN; không render dữ liệu riêng của khách. `<x-vani::hook-slot>` vẫn chỉ nối thêm view `SlotView` hoặc `Htmlable`; riêng slot `layout.head` không bọc nội dung trong phần tử HTML.

### 4.2 Hook tại kết quả service (0.3.47, [ADR-036](../19-adr/ADR-036-service-result-hooks.md))

Core gọi `vani_filter('vani.service.<module>.<kết quả>', $value, ...$args)` ngay tại kết quả của service nghiệp vụ; plugin đăng ký listener bằng `vani_add_filter()` trong `boot()`. Các helper `vani_*` đi qua HookManager (khai báo, kiểm kiểu, plugin sở hữu, đo thời gian). Filter phải trả **cùng kiểu** với đầu vào (DTO bất biến: tạo bản mới qua `with*()`/constructor).

| Hook | Giá trị | Args thêm | Lỗi listener | Mục đích |
|---|---|---|---|---|
| `vani.service.checkout.totals` | `TotalsContext` | — | fail (đặt hàng thất bại) | Sửa tổng sau mọi calculator, **trước** guard: giảm giá dòng, `Adjustment`, phí giao. Quote và đặt hàng cùng kết quả |
| `vani.service.checkout.quote` | `CheckoutQuote` | `CheckoutRequest` | skip | Chỉ hiển thị (không đổi tiền ở đây) |
| `vani.service.pricing.prices` | `array<int, ResolvedPrice>` | `PricingContext` | fail | Đổi giá hiệu lực theo variant (storefront, giỏ, checkout); không thêm/xoá khoá |
| `vani.service.promotion.result` | `PromotionResult` | `PromotionContext` | fail | Sửa khuyến mãi đã áp / voucher bị từ chối |
| `vani.service.catalog.search` | `array` (items, total, facets…) | `ProductFilters`, locale | skip | Bổ sung item/facet danh sách sản phẩm |
| `vani.service.catalog.product_detail` | `array` | slug, locale | skip | Bổ sung chi tiết sản phẩm (khi tìm thấy) |
| `vani.service.catalog.category_tree` | `list<array>` | locale | skip | Cây danh mục storefront |
| `vani.service.ordering.order_detail` | `OrderDetail` | `Order`, masked | skip | Chi tiết đơn cho Admin/khách |
| `vani.service.customer.profile_attributes` | `array` | customerId | fail | Dữ liệu khách tự sửa hồ sơ trước khi lưu |
| `vani.service.notification.request` | `NotificationRequest` | — | fail | Yêu cầu gửi thông báo trước khi chọn kênh/mẫu |
| `vani.service.fulfillment.shipment_data` | `ShipmentData` | `Shipment` | fail | Dữ liệu gửi hãng vận chuyển (không đổi `codAmount`) |
| `vani.service.catalog.products_by_ids` | `list<array>` | locale | skip | Thẻ sản phẩm theo id (bán chạy, đã xem) — 0.3.48 |
| `vani.service.catalog.brands` / `catalog.brand` | `list<array>` / `array` | — / slug | skip | Danh sách / trang thương hiệu — 0.3.48 |
| `vani.service.payment.view` | `PaymentView` | `Payment` | skip | Hướng dẫn thanh toán hiển thị cho khách (không đổi tiền/trạng thái) — 0.3.48 |
| `vani.service.returns.view` | `ReturnView` | `ReturnRequest` | skip | Yêu cầu đổi/trả hiển thị cho Admin/khách — 0.3.48 |

```php
// Plugin: giảm theo điểm thưởng (tương đương "thêm một dòng total" ở hệ tham chiếu)
vani_add_filter('vani.service.checkout.totals', function (TotalsContext $context): TotalsContext {
    return $context->withAdjustment(new Adjustment('loyalty', 'vani.loyalty', null, 'Đổi điểm', $context->money(-50_000)))
        ->withLines(/* phân bổ 50.000đ vào giảm giá các dòng */);
});
```

**Domain event → hook action (0.3.48):** mọi event `Modules\<Module>\Events\<Event>` tự thành action `vani.event.<module>.<event>` (snake_case), tham số là đối tượng event — plugin có thể đăng ký listener bằng `vani_add_action('vani.event.ordering.order_placed', fn (OrderPlaced $e) => …)`. Event Core phát **sau commit**; listener chạy đồng bộ trong request nên việc chậm/gọi mạng đẩy vào queue; lỗi listener bị bỏ qua (`on_error: skip`). Danh sách event: §3.

Tiền chỉ đổi qua `totals`, `pricing.prices`, `promotion.result`. Phân bổ giảm giá về dòng (`TotalsLine::withDiscount`) là bắt buộc: `grandTotal` = Σ dòng + phí giao; `Adjustment` là bản ghi truy vết (lưu `order_adjustments`), không tự trừ tiền.

## 5. Registry (qua `PluginServiceProvider`)

| Registry | Ví dụ |
|---|---|
| `adminMenu()` | Mục menu Admin + permission — Implemented |
| `adminPages($namespace, $path)` | Trang Inertia của plugin (`'Promotion::Rules/Index'`) — Implemented. Giao diện dùng bộ component `@admin/Components` (`DataTable`, `Pagination`, `Select`, `MultiSelect`, `Combobox`, `DateInput`, `StatusBadge`, `MoneyText`, `confirmAction()`, `promptFields()`, `useMoney()`, `useDateTime()`…), không import `reka-ui` trực tiếp ([ADR-039](../19-adr/ADR-039-admin-ui-reka.md)) |
| `permissions()` | Khai báo permission — Implemented (gán role mẫu: Designed) |
| `settings()` | Khai báo cấu hình theo scope → form tự sinh (Admin → Cấu hình); secret được mã hoá — Implemented |
| `storefrontRoutes()` | Storefront API của plugin dưới `/api/storefront/v1/x/{slug}/…` (không ghi đè route Core) — Implemented (0.3.3) |
| `accountPage(key, label, route)` | Mục menu tài khoản khách native trỏ tới trang của plugin — Implemented (0.3.9) |
| `storefrontPages(file, prefix, cacheable)` theo ngôn ngữ | `prefix` nhận mảng `['vi' => 'tin-tuc', 'en' => 'news']` → thêm `/en/news/…` (tên `storefront.en.p.{slug}.…`); chuỗi = chỉ ngôn ngữ mặc định. Sinh URL bằng `vani_route()` — Implemented (0.3.59, ADR-038) |
| `storefrontPages()` + `storefrontViews()` | Trang native `/p/{slug}/…` trong layout theme; theme override view plugin tại `custom/theme/<theme>/plugins/{slug}/` — Implemented (0.3.3). `prefix:` (0.3.14): URL đẹp `/{prefix}/…` (vd. `/tin-tuc`), không trùng route Core (`StorefrontPrefixes::RESERVED`, arch test) hay plugin khác — tham chiếu `vani.cms`. `cacheable: true` (0.3.34): trang không phiên, CDN cache được ([storefront §5](../14-storefront/storefront.md)) |
| Helper plugin/theme (ADR-037) | `vani_setting`, `vani_money`, `vani_locale`, `vani_customer`, `vani_staff`, `vani_admin_url`, `vani_image` (`Catalog\Contracts\MediaUrls`), `vani_link`, `vani_route`, `vani_asset`, `vani_plugin` (`Extension\Contracts\PluginDirectory`) — Implemented (0.3.69). Class trong `modules/` không dùng (arch test) |
| `accountRoutes(file, segment)` | Trang plugin dưới `/tai-khoan/{segment}` (`/en/account/…`), bắt buộc đăng nhập — Implemented (0.3.76, tham chiếu `vani.wishlist`). Gắn menu bằng `accountPage()` |
| `adminApiRoutes()` | `/api/admin/v1/plugins/{code}/…` |
| `webhookRoutes()` | `/api/integrations/{slug}/…` — Implemented |
| `adminRoutes()` | `/{VANI_ADMIN_PATH}/plugins/{slug}/…` (Inertia): Implemented |
| `schedule(fn (Schedule $s) => …)` | Tác vụ định kỳ, chỉ chạy khi plugin bật ở ít nhất một phạm vi — Implemented (0.3) |
| `NotificationCatalog::define()` (contract Notification) | Loại tin + biến + mẫu mặc định theo kênh; mẫu trong DB thắng — Implemented (0.3) |
| `adminFormSection(resource, key, label, fields, load, save)` | Phần form của plugin trên form tạo/sửa của Core (`product`); `save` trong transaction lưu của Core; input `extensions.<id-plugin-dạng-slug>.<key>.<field>` — Implemented (0.3.2) |
| `adminColumn(resource, key, label, resolve)` | Cột trên danh sách (`product`, `order`, `customer`), `resolve` nhận cả trang id — Implemented (0.3.2) |
| `adminFilter(resource, key, label, options, apply)` | Bộ lọc danh sách, `apply` trả id thoả (giao nhau giữa các bộ lọc) — Implemented (0.3.2) |
| `adminAction(resource, key, label, permission, handle, scope)` | Nút trên trang chi tiết / chọn nhiều (danh sách đơn); Core kiểm tra quyền + audit — Implemented (0.3.2) |
| `adminTab(resource, key, label, rows)` | Tab nhãn/giá trị trên trang chi tiết (`order`, `customer`) — Implemented (0.3.2) |
| Trường checkout của plugin | Thay cho `checkoutFields()`: client gửi `extra[<plugin id>]` ở đặt hàng → plugin kiểm tra qua `vani.checkout.before_validate` và lưu qua `vani.order.before_create` — Implemented (0.3) |
| `integrationMessageTypes()` | Loại message tích hợp mới + JSON Schema |

## 6. Dữ liệu của plugin

- Bảng riêng `plg_<plugin>_*`, tham chiếu ID của Core bằng FK (`ON DELETE RESTRICT`). Core **không** FK sang bảng plugin.
- Cột `meta` (JSON) trên `orders`, `order_lines`, `carts`, `customers`, `styles`, `variants` dùng cho dữ liệu nhỏ, **namespace theo mã plugin** (`meta.einvoice.tax_code`). Không dùng cho dữ liệu cần lọc hoặc báo cáo.
- Plugin **không** thêm cột vào bảng Core, không sửa migration Core ([ADR-027](../19-adr/ADR-027-plugin-data-no-core-columns.md)).

## 7. Extension point còn thiếu theo plugin dự kiến

> Thiết kế đầy đủ và lộ trình theo đợt (W1–W6): [extension-surface-v2](extension-surface-v2.md) ([ADR-030](../19-adr/ADR-030-extension-surface-v2.md)). Bảng dưới giữ các điểm đã nêu trước đó; khi một đợt hoàn thành, chuyển dòng lên §1–§5.

Microkernel chỉ đúng khi plugin trong [plugin-catalog](../05-plugin/plugin-catalog.md) viết được **mà không sửa Core** ([ADR-029](../19-adr/ADR-029-commerce-microkernel.md)). Bảng dưới đối chiếu plugin dự kiến với extension point chúng cần nhưng Core **chưa có**. Làm extension point **trước** plugin, theo đợt của plugin dùng nó; mỗi điểm cần implementation tham chiếu + contract test (R26).

| Extension point thiếu | Loại | Plugin cần | Đợt |
|---|---|---|---|
| `FulfillmentMethod` (`pickup`) + `ShipmentRecorder` | Contract | `vani.store-omnichannel` | P2 |
| `DashboardWidget` + quyền đọc báo cáo | Contract | `vani.reports` (Reporting thành plugin) | P2 |
| `adminApiRoutes()` | Registry | POS/app quản trị của plugin | P3 |
| `integrationMessageTypes()` + JSON Schema | Registry | connector ERP | Khi chốt ERP |
| Phụ thu theo tuỳ chọn dòng (dòng phí trong totals; grandTotal hiện = Σ dòng + ship) | Totals | khắc tên có phí, `vani.product-bundle` | Khi có plugin cần |

Khi thêm một extension point từ bảng này: chuyển dòng tương ứng lên §1–§5, ghi [CHANGELOG-extension](CHANGELOG-extension.md), xoá khỏi bảng.
