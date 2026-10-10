# Danh mục Extension Points (public API v1)

> Trạng thái: **Designed**. Bảng này là **nguồn duy nhất** liệt kê extension point public. Thêm/đổi phải theo [compatibility policy](extension-model.md).
>
> **Định hướng [ADR-028](../19-adr/ADR-028-single-store-brand-as-catalog.md)** (một cửa hàng): plugin bật/tắt toàn cửa hàng; tham số kênh/brand-phạm vi trên contract (`PriceResolver`, `AvailabilityReader`, `ChannelDirectory`, `CurrentContext`) và `brandId` trên event sẽ bị gỡ/deprecated ở slice 12 (Core `0.3.0`, ghi [CHANGELOG-extension](CHANGELOG-extension.md)). Các dòng dưới đây mô tả API hiện tại.

## 1. Contract: capability thay thế được

Plugin đăng ký bằng `contribute(<Contract>::TAG, Implementation::class)` — **mọi tag là hằng `TAG` trên interface trong `Contracts`** (arch test chặn định nghĩa tag ở tầng khác; hằng cũ trong `Application` còn làm alias `@deprecated`). Registry của Core lọc theo trạng thái bật của plugin (hiện theo scope owner/brand/channel; sau slice 12: bật/tắt toàn cửa hàng).

| Contract | Tag | Context | Mặc định trong Core | Tài liệu |
|---|---|---|---|---|
| `PaymentGateway` | `vani.payment.gateways` | Payment | `cod`, `manual_bank_transfer`. **Implemented** + bộ contract test `PaymentGatewayContract`. Interface tuỳ chọn: `CapturesLater` (0.3.5), `CallbackResponder` (0.3.15: phản hồi IPN theo `CallbackOutcome`, tham chiếu `vani.vnpay`) | [payment](../10-payment/payment.md) |
| `ShippingCarrier` | `vani.shipping.carriers` | Fulfillment | `manual`. **Implemented** + bộ contract test `ShippingCarrierContract` (phí ở checkout: `ShippingRateProvider`) | [fulfillment](../09-order/fulfillment.md) |
| `FulfillmentMethod` | `vani.fulfillment.methods` | Fulfillment | `delivery` | [fulfillment](../09-order/fulfillment.md) |
| `SourcingStrategy` | `vani.fulfillment.sourcing` | Fulfillment | `reserved_locations` (**Implemented**; đề xuất phải khớp hàng đang giữ) | [fulfillment](../09-order/fulfillment.md) |
| `InventoryStrategy` | `vani.inventory.strategies` | Inventory | `standard` (ATS = on_hand − reserved − safety). **Implemented**; strategy chỉ giảm được ATS (Core kẹp `min(strategy, standard)`) | [inventory](../08-inventory/inventory.md) |
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
| `ImageFormat` | `catalog.image-format` | Catalog | Không có mặc định (giữ định dạng gốc); `webp` là plugin `vani.media-webp` (0.3.28) | [storefront](../14-storefront/storefront.md) |
| `OtpSender` | `vani.customer.otp_senders` | Customer | `email`, `log` (chỉ dev). **Implemented**: thử theo `priority()` giảm dần; kênh ném `OtpDeliveryFailed` → kênh kế tiếp. Plugin: `vani.zalo-zns` (60), `vani.sms-brandname` (50) | [customer](../03-domains/customer.md) |
| `AuthProvider` | `vani.customer.auth_providers` | Customer | — (plugin). **Implemented** (0.3.7): Core lo `state`, danh sách `redirect_uri`, ghép danh tính (đã liên kết → SĐT đã xác minh → email đã xác minh của khách đang có), token; contract test `AuthProviderContract`. Chưa có plugin thật (dự kiến `vani.social-login`) | [extension-surface-v2 §4.E](extension-surface-v2.md) |
| `NotificationChannel` | `vani.notification.channels` | Notification | `mail`. **Implemented**; plugin: `sms` (`vani.sms-brandname`), `zns` (`vani.zalo-zns`) | [notification](../03-domains/notification.md) |
| `Connector` | `vani.integration.connectors` | Integration | — (connector là plugin). **Implemented** (slice 11): nhận message outbox theo `supports()`, trả `DeliveryResult` ok/retryable/permanent | [integration-platform](../11-integration/integration-platform.md) |
| `InboundHandler` | `vani.integration.inbound` | Integration | —. **Implemented** (slice 11): xử lý message inbox theo `(system, message_type)`, trả `DeliveryResult` (thêm `stale`) | [integration-platform](../11-integration/integration-platform.md) |
| `ErpConnector` (extends `Connector`) | `vani.integration.erp` | Integration | — | [erp-integration](../11-integration/erp-integration.md) |
| `CartLineOption` | `vani.cart.line_options` | Cart | — (plugin). **Implemented** (0.3.4): tuỳ chọn dòng giỏ theo plugin id, chuẩn hoá + từ chối, chụp sang dòng đơn; không đổi giá (phụ thu: Designed); tham chiếu `vani.hello-world` (lời chúc gói quà) | [extension-surface-v2 §4.C](extension-surface-v2.md) |
| `PluginHealthCheck` | `vani.health.checks` | Extension | — (plugin). **Implemented** (0.3.6): `vani:plugin:health` + doctor + trang Admin Plugin; tham chiếu `vani.sms-brandname`, `vani.zalo-zns` (thiếu khoá, sandbox) | [extension-surface-v2 §4.G](extension-surface-v2.md) |
| `StorefrontEnricher` | `vani.storefront.enrichers` | Storefront | — (plugin). **Implemented** (0.3.1): làm giàu `product_card`/`product`/`cart`/`order` dưới `extensions.<plugin-id>`, batch, lỗi bị bỏ; contract test `StorefrontEnricherContract`; tham chiếu `vani.hello-world` | [extension-surface-v2 §4.B](extension-surface-v2.md) |
| `StorefrontBlock` | `vani.storefront.blocks` | Storefront | `hero`, `product_grid`, `brand_grid`, `rich_text`. **Implemented** (0.3.10): page builder trang chủ (Admin → Giao diện), contract test `StorefrontBlockContract`, tham chiếu `vani.hello-world` | [storefront §4](../14-storefront/storefront.md) |
| `SitemapProvider` | `vani.storefront.sitemap` | Storefront | — (plugin). **Implemented** (0.3.14): URL công khai của plugin vào `/sitemap.xml`, lỗi bị bỏ; tham chiếu `vani.cms` | [storefront §5](../14-storefront/storefront.md) |
| `DashboardWidget` | `vani.admin.dashboard.widgets` | Extension | — (plugin). **Implemented** (0.3.12): ô Tổng quan trả `Metric`/`Series`/`Table`, Core lọc quyền + cô lập lỗi; tham chiếu `vani.reports` | [extension-surface-v2 §4.G](extension-surface-v2.md) |
| `ReportProvider` | `vani.admin.reports` | Extension | — (plugin). **Implemented** (0.3.12): Admin → Báo cáo, `run(ReportPeriod): ReportResult`, Core dựng trang + CSV; tham chiếu `vani.reports` | [extension-surface-v2 §4.G](extension-surface-v2.md) |

Extension contract không có abstract base: mở rộng bằng field tuỳ chọn hoặc interface bổ sung tuỳ chọn ([extension-model §5](extension-model.md)); mọi thay đổi ghi ở [CHANGELOG-extension](CHANGELOG-extension.md). Mỗi extension contract có bộ **contract test** trong `Modules\<Ctx>\Testing` mà plugin phải chạy ([testing §6](../17-testing/testing.md)).

## 2. Service contract: plugin gọi Core

| Contract | Chức năng | Context |
|---|---|---|
| `CatalogReader` | Đọc catalog đang hiển thị (cây danh mục, tìm sản phẩm, PDP kèm variant) (hiện lọc theo brand của kênh; sau slice 12: toàn cửa hàng, thêm danh sách/chi tiết brand). **Implemented** | Catalog |
| `VariantDirectory` | Tra variant (theo mã style, theo id) cho module khác. **Implemented** | Catalog |
| `CatalogImporter` | Nhập sản phẩm (`upsertProduct(ProductImport)`): idempotent theo mã style, tạo thương hiệu/danh mục/màu/size còn thiếu, ảnh từ file local qua thư viện media, sinh variant; không ghi đè nội dung đã sửa. **Implemented** (0.3.27), tham chiếu `vani.demo-catalog` | Catalog |
| `ChannelDirectory` | Kênh bán của một brand. **Implemented**, sẽ gỡ cùng module Channel (slice 12) | Channel |
| `PriceResolver` | Giá hiệu lực của variant theo channel (nhóm khách: Designed). **Implemented** | Pricing |
| `PriceImporter` | Đặt giá niêm yết (bảng `base`) theo SKU. **Implemented** (0.3.27) | Pricing |
| `InventoryReservation` | `reserve`, `release`, `commit` (**Implemented**) | Inventory |
| `StockImporter` | Đặt tồn tuyệt đối theo SKU tại kho do VaniShop quản lý (movement `sync` + audit; kho authority ngoài dùng `InventorySync`). **Implemented** (0.3.27) | Inventory |
| `InventoryAdjuster` | Điều chỉnh on-hand có lý do (movement) — hiện là service nội bộ `StockAdjustmentService`, chưa công bố contract (chờ Integration) | Inventory |
| `AvailabilityReader` | ATS theo channel (**Implemented**; theo location: chưa) | Inventory |
| `Carts` | Giỏ: tạo, xem, thêm/sửa/xoá dòng, gộp, khoá cho checkout. **Implemented** | Cart |
| `PromotionEngine` | Đánh giá khuyến mãi, ghi nhận/hoàn lượt. **Implemented** | Promotion |
| `Checkout` | `quote`, `placeOrder` (idempotent). **Implemented** | Checkout |
| `ShippingAddresses` | Địa chỉ giao hàng theo danh mục địa giới đang dùng (`directory()`, `normalize()`): cùng quy tắc cho checkout, sổ địa chỉ khách, Admin sửa địa chỉ đơn. **Implemented** (0.3.13) | Checkout |
| `OrderWriter` | Tạo đơn từ bản nháp đã tính (trong transaction PlaceOrder). **Implemented** | Ordering |
| `OrderReader` | Đọc đơn (DTO snapshot). **Implemented** | Ordering |
| `OrderStatistics` | Số liệu bán hàng tổng hợp: tổng, theo ngày (múi giờ), theo thanh toán/kênh/sản phẩm/thương hiệu, đếm theo trạng thái. **Implemented** (0.3.12) | Ordering |
| `CustomerOrders` | Tra cứu/xem/huỷ đơn cho khách vãng lai. **Implemented** | Ordering |
| `OrderTransitions` | Chuyển trạng thái qua state machine, cập nhật `payment_status`. **Implemented** | Ordering |
| `Payments` | Phương thức khả dụng, tạo/khởi tạo payment, xác nhận thủ công, hoàn tiền (thay `PaymentRecorder` trong thiết kế). **Implemented** | Payment |
| `ShipmentReader` | Vận đơn của một đơn (Storefront, panel Admin). **Implemented**. Ghi nhận trạng thái đi qua webhook chung/Admin, chưa công bố `ShipmentRecorder` | Fulfillment |
| `InventoryReturns` | Nhập lại hàng về kho (movement `return`). **Implemented** | Inventory |
| `Returns` | Tạo/xem/huỷ yêu cầu đổi/trả, số lượng còn trả được. **Implemented** | Returns |
| `CustomerDirectory` | Tìm/tạo khách theo SĐT, đọc consent | Customer |
| `CustomerAccounts` | Khách tự quản lý hồ sơ, mật khẩu, sổ địa chỉ (bề mặt ngoài Storefront API, vd. native storefront). **Implemented** (0.3.13) | Customer |
| `Settings` | Đọc/ghi cấu hình, khai báo định nghĩa. **Implemented** (hiện kế thừa kênh → brand → pháp nhân → owner; sau slice 12: một cấp cửa hàng) | Tenancy |
| `IntegrationOutbox` | Đưa message ra ngoài có đảm bảo | Integration |
| `CurrentContext` | Locale/actor hiện tại; `runAs()` (brand/channel: bỏ ở slice 12) | Shared |
| `Authorizer` | Kiểm tra quyền theo scope | Identity |

## 3. Domain Events

Dispatch **sau commit**. Payload là DTO bất biến trong `Modules\<Ctx>\Events`. Plugin nghe qua `PluginServiceProvider::onEvent()`. Hiện event gắn với đơn mang `brandId` để lọc theo phạm vi bật plugin; sau slice 12 plugin bật toàn cửa hàng nên `brandId` thành `@deprecated` (không còn dùng để lọc).

| Context | Events |
|---|---|
| Catalog | `ProductCreated`, `ProductUpdated`, `ProductArchived`, `VariantCreated` (**Implemented**, `Modules\Catalog\Events`, sau commit) |
| Pricing | `PriceChanged` (**Implemented**) |
| Inventory | `StockReserved`, `StockReleased`, `StockCommitted`, `StockAdjusted`, `AvailabilityChanged` (**Implemented**) |
| Customer | `CustomerRegistered`, `CustomerMerged`, `ConsentChanged` |
| Cart | `CartUpdated`, `CartAbandoned` (**Implemented**; `CartAbandoned` từ 0.3.3, giỏ của khách không hoạt động quá ngưỡng) |
| Ordering | `OrderPlaced`, `OrderConfirmed`, `OrderCancelled`, `OrderCompleted` (**Implemented**; `OrderCompleted` từ 0.3.1, khi hết hạn đổi trả sau giao) |
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
| `vani.product.before_save` | validate | không (chạy trước transaction) | Chặn khi lưu sản phẩm (quy tắc riêng của cửa hàng); tham số `ProductDraft`. **Implemented** |
| `vani.product.after_save` | action | có (chỉ ghi DB) | Plugin lưu dữ liệu mở rộng của sản phẩm; tham số `(styleId, brandId)`. **Implemented** |
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
| `vani.admin.order.sidebar` | slot | — | Panel trên trang đơn Admin; tham số `OrderDetail`, trả `{title, rows[{label, value}], link?}`. **Implemented** (Payment dùng) |

### 4.1 Slot storefront (Implemented 2026-10-02 cùng theme `vani-base`, khai báo ở `modules/Storefront/hooks.php`)

Theo [ADR-025](../19-adr/ADR-025-native-storefront-ssr-slots.md): listener trả `Modules\Storefront\Contracts\Data\SlotView` (view của plugin + dữ liệu) hoặc `Htmlable`; theme render tại vị trí slot bằng `<x-vani::hook-slot>`, chỉ nối thêm, lỗi một listener (hoặc lỗi lúc render) bị bỏ qua, chuỗi thô bị bỏ. Các slot dưới đây là public API (`since 0.3`), trừ `account.menu` (chờ trang tài khoản native).

| Slot | Vị trí | Ví dụ plugin |
|---|---|---|
| `vani.storefront.layout.head` | Cuối `<head>` | Pixel tracking, meta xác minh |
| `vani.storefront.layout.body_end` | Cuối `<body>` | Chat, script đo lường |
| `vani.storefront.plp.card_badges` | Nhãn trên thẻ sản phẩm ở danh sách | "Mới", "Bán chạy", "Freeship" |
| `vani.storefront.pdp.after_title` | Dưới tên sản phẩm | Đánh giá sao |
| `vani.storefront.pdp.after_price` | Dưới giá | Trả góp, điểm thưởng dự kiến |
| `vani.storefront.pdp.after_add_to_cart` | Dưới nút thêm giỏ | Bảng size, cam kết đổi trả |
| `vani.storefront.pdp.after_details` | Sau mô tả | Lookbook, phối đồ, sản phẩm gợi ý |
| `vani.storefront.cart.after_lines` | Sau danh sách dòng giỏ | Tiến độ freeship, upsell |
| `vani.storefront.checkout.after_shipping` | Sau chọn phương thức giao | Ghi chú giao hàng, gói quà |
| `vani.storefront.checkout.before_submit` | Trước nút đặt hàng | Xuất hoá đơn điện tử (trường `extra[<plugin id>]`) |
| `vani.storefront.order.after_summary` | Trang cảm ơn / chi tiết đơn | Hướng dẫn chuyển khoản, điểm đã cộng |
| `vani.storefront.header.nav` / `header.actions` | Menu chính / cụm thao tác cạnh giỏ (0.3.1) | Trang plugin; wishlist, điểm |
| `vani.storefront.footer.columns` | Cột footer (0.3.1) | Chính sách, đăng ký nhận tin |
| `vani.storefront.plp.filters` | Bộ lọc bổ sung trang danh sách (0.3.1) | Lọc theo dữ liệu plugin |
| `vani.storefront.pdp.gallery_after` | Dưới ảnh sản phẩm (0.3.1) | Video, 360° |
| `vani.storefront.checkout.contact_after` / `address_after` / `payment_after` | Sau từng bước checkout (0.3.1); trường `extra[<plugin id>]` | Mã số thuế HĐĐT, ghi chú giao, ưu đãi theo cổng |
| `vani.storefront.account.menu` / `account.dashboard` / `account.order_detail` | Menu, tổng quan, chi tiết đơn trong `/tai-khoan` (0.3.9); mục menu có trang riêng: `accountPage()` | Điểm thưởng, ví, hoá đơn |

## 5. Registry (qua `PluginServiceProvider`)

| Registry | Ví dụ |
|---|---|
| `adminMenu()` | Mục menu Admin + permission — Implemented |
| `adminPages($namespace, $path)` | Trang Inertia của plugin (`'Promotion::Rules/Index'`) — Implemented |
| `permissions()` | Khai báo permission — Implemented (gán role mẫu: Designed) |
| `settings()` | Khai báo cấu hình theo scope → form tự sinh (Admin → Cấu hình); secret được mã hoá — Implemented |
| `storefrontRoutes()` | Storefront API của plugin dưới `/api/storefront/v1/x/{slug}/…` (không ghi đè route Core) — Implemented (0.3.3) |
| `accountPage(key, label, route)` | Mục menu tài khoản khách native trỏ tới trang của plugin — Implemented (0.3.9) |
| `storefrontPages()` + `storefrontViews()` | Trang native `/p/{slug}/…` trong layout theme; theme override view plugin tại `custom/theme/<theme>/plugins/{slug}/` — Implemented (0.3.3). `prefix:` (0.3.14): URL đẹp `/{prefix}/…` (vd. `/tin-tuc`), không trùng route Core (`StorefrontPrefixes::RESERVED`, arch test) hay plugin khác — tham chiếu `vani.cms`. `cacheable: true` (0.3.34): trang không phiên, CDN cache được ([storefront §5](../14-storefront/storefront.md)) |
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
