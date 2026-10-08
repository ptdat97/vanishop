# Changelog — public API cho plugin

Ghi mọi thay đổi của extension point public: `Modules\*\Contracts`, `Modules\*\Events`, `PluginServiceProvider`, hook trong `hooks.php`, registry. Nguồn đối chiếu tự động: `tests/Architecture/public-api.snapshot` (test đỏ khi API đổi mà snapshot chưa cập nhật). Chính sách: [extension-model §5](extension-model.md).

Plugin khai báo `requires.vanishop` theo Composer semver. Ở giai đoạn `0.x`, tăng số giữa (`0.1` → `0.2`) được coi là **có thể phá vỡ**, nên `^0.1` không nhận Core `0.2.x`.

## 0.3.34 — 2026-10-08

Cache CDN cho trang công khai của storefront (roadmap Phase 7, [storefront §5](../14-storefront/storefront.md)).

### Thêm
- `PluginServiceProvider::storefrontPages($file, $prefix, bool $cacheable = false)`: `true` → trang của plugin chạy không phiên, CDN cache được (middleware `vani.page-cache`). `vani.cms` 1.2.0 dùng cho `/trang`, `/tin-tuc`.
- `Shared\Http\SessionlessRoutes::MIDDLEWARE`; endpoint `GET /_vani/phien`; tiền tố giữ chỗ `cache`, `_vani`.

### Đổi hành vi
- Trang chủ, danh mục, thương hiệu, tìm kiếm, sản phẩm không còn phiên: view/slot trên các trang này **không** đọc được phiên, `old()`, khách đăng nhập (`request()->attributes->get('customer')` luôn null). Plugin có slot cá nhân hoá trên các trang này phải chuyển sang tải bằng JS/API.
- `POST /gio-hang` miễn CSRF (cookie phiên SameSite=Lax). Livewire không tự chèn script vào trang HTML.

## 0.3.33 — 2026-10-08

Capability giữa plugin (roadmap Phase 5). Chỉ thêm.

### Thêm
- Manifest `requires.capabilities`: danh sách tag extension point cần ít nhất một implementation đang bật (Core hoặc plugin bất kỳ). `vani:plugin:enable` từ chối khi thiếu (`missing_capability`); tắt/ngừng plugin là nguồn cuối cùng mà plugin đang chạy khác cần bị chặn; `vani:plugin:upgrade` kiểm tra capability mới khai; doctor `capability_missing`.

## 0.3.32 — 2026-10-08

Đổi hàng (roadmap Phase 2, [order §7.1](../09-order/order.md)). Chỉ thêm.

### Thêm
- `Checkout\Contracts\ReplacementOrders::place(ReplacementOrderRequest): PlacedOrder`, DTO `ReplacementOrderRequest`, `ReplacementLine`, lỗi `ReplacementUnavailable` (`checkout.replacement_unavailable`).
- `Returns::request(..., array $exchanges = [])` (order_line_id => variant thay thế); `ReturnView::$resolution`, `$replacementOrderId`; `ReturnRejected::exchangeInvalid()`, `exchangeUnavailable()`; `ReturnResolved::$replacementOrderId`.
- `OrderDraft::$parentOrderId`, `OrderData::$parentOrderId`, `OrderLineData::$unitAmount`.
- Nguồn đơn `exchange`; payload `vanishop.order.v1` thêm `parent_order_number`; `return.resolved` thêm `resolution`, `replacement_order_number`.

## 0.3.31 — 2026-10-08

Tính lại khuyến mãi khi khách bớt hàng (roadmap Phase 2, [order §2.1](../09-order/order.md)).

### Thêm
- `Promotion\Contracts\CartContentRule` (đánh dấu, extends `PromotionRule`): rule chỉ phụ thuộc dòng hàng — được kiểm tra lại khi khách bớt hàng. `vani.promotion-rules` 0.2.0: `min_order_subtotal`, `min_quantity`, `in_collections`, `in_brands` đánh dấu; `first_order_only` không.
- `PromotionEngine::recheck(PromotionContext, list<int>)`, `PromotionEngine::adjustUsage(int, array)` (Core là implementation duy nhất; plugin không implement contract này).
- `Ordering\Contracts\Data\LineCancellationCause` (`shop` | `customer`); `OrderLineDraft` thêm `styleId`, `promotions` (tham số tuỳ chọn cuối).
- `OrderLinesCancelled` thêm `$cause`, `$promotionClawback` (mặc định `'shop'`, `0`); `OrderReader::cancellations()` thêm `promotion_clawback`; payload `order.lines_cancelled` thêm `promotion_clawback`.

### Đổi hành vi
- `OrderLinesCancelled::$amount` / payload `amount` / `cancellations()[].amount` là số tiền đơn giảm **ròng** (phần huỷ − khuyến mãi thu hồi). Chỉ khác số cũ khi `cause = customer` và có thu hồi.
- Huỷ một phần (mọi nguyên nhân) giảm `promotion_usages.discount_amount` và `promotions.budget_used_amount` về giảm giá còn trên đơn (đơn đặt từ 0.3.31).

## 0.3.30 — 2026-10-08

Cảnh báo tự động (roadmap Phase 6). Chỉ thêm.

### Thêm
- `Shared\Contracts\AlertChannel` (tag `vani.alert-channels`): kênh nhận cảnh báo; Core có `mail` (`VANI_ALERT_EMAILS`). DTO `Shared\Contracts\Data\Alert` (`key`, `severity` critical/high/normal, `state` firing/reminder/resolved, `subject()`).
- `vani:alerts:check [--dry-run]` (mỗi phút), bảng `alert_states`, metric `alerts.fired`, ô Tổng quan `open_alerts`.

## 0.3.29 — 2026-10-08

Thư viện ảnh dùng chung cho plugin. Chỉ thêm.

### Thêm
- `Catalog\Contracts\MediaDirectory`: `find(ids)` → `MediaData` (`urlFor(width)`, `srcset()`), `syncUsages(ownerType, ownerId, role, mediaIds)`, `releaseUsages(ownerType, ?ownerId)`. Plugin lưu id ảnh (chọn qua `MediaPicker` ở Admin — `@admin/Components/Media/MediaPicker.vue`) và khai báo nơi dùng để Thư viện ảnh chặn xoá ảnh đang dùng.
- Quyền `media.view`, `media.manage`; prop Inertia chia sẻ `urls.media`.
- `vani.cms` 1.1.0 dùng contract này (ảnh bìa `cover_media_id`, ảnh trong bài `media:ID`); bỏ endpoint `POST /{admin}/plugins/vani-cms/uploads`.

## 0.3.28 — 2026-10-07

Cache ảnh thu nhỏ tại `public/cache` + extension point định dạng ảnh. Chỉ thêm (đổi hành vi URL ảnh, xem dưới).

### Thêm
- `Catalog\Contracts\ImageFormat` (tag `catalog.image-format`, kind manifest `image_format`): `supports(mime)`, `extension()`, `encoder(quality)` — chọn định dạng đầu ra của ảnh thu nhỏ. Không có plugin → giữ định dạng gốc.
- Plugin `vani.media-webp`: JPEG (+ PNG, tuỳ chọn) → WebP; cấu hình `convert_png`, `quality`.
- `vani:media:cache --clear|--warm`.

### Đổi hành vi
- `Media::url($width)` trả `/cache/media/{ab}/{checksum}-w{width}.{ext}` (tạo khi có request đầu tiên, ghi vào `vanishop.media.cache.path`), không còn tạo sẵn WebP 400/800/1600 trên disk media lúc tải ảnh lên. Chiều rộng cấu hình ở `vanishop.media.cache.widths` (mặc định 160/400/800/1600). Muốn WebP như trước: bật `vani.media-webp`.

## 0.3.27 — 2026-10-07

Service contract nhập dữ liệu + plugin dữ liệu demo. Chỉ thêm.

### Thêm
- `Catalog\Contracts\CatalogImporter::upsertProduct(ProductImport): ImportedProduct`, DTO `ProductImport`, `ImportedProduct`.
- `Pricing\Contracts\PriceImporter::setBasePrices(array)`.
- `Inventory\Contracts\StockImporter::setOnHand(array, ?string, string)`.
- Plugin `vani.demo-catalog`: `vani:demo:catalog [--source] [--limit] [--dry-run]` nhập sản phẩm demo có ảnh thật (VaniCommerce/public/image/catalog/products), là implementation tham chiếu cho 3 contract trên (R26). Các contract này cũng dùng cho đồng bộ catalog/giá từ ERP sau này.

## 0.3.26 — 2026-10-07

Hợp đồng Integration API (roadmap Phase 6). Không đổi API plugin.

### Thêm
- OpenAPI 3.1 `docs/api/openapi/integration-v1.json` + schema phản hồi `docs/api/schemas/integration/*.json` (gồm `error.v1.json`).

### Đổi hành vi
- `POST /api/integration/v1/orders/{number}/acknowledgements` thiếu/sai `Idempotency-Key`: lỗi `400 integration.idempotency_key_required` (trước: `400 http.400`).

## 0.3.25 — 2026-10-07

Observability tối thiểu (roadmap Phase 6). Chỉ thêm.

### Thêm
- `Shared\Contracts\Metrics` (`increment`, `gauge`): service contract ghi metric. Plugin dùng được, với tên dạng `plugin.<id>.<tên>` để không đụng tên của Core. Implementation phải nuốt lỗi của hệ giám sát.
- Danh mục metric công khai của Core: [observability §4.1](../16-observability/observability.md). `vani:metrics:snapshot` (mỗi phút), thẻ Pulse "Thương mại".
- `GET /health` (Core route); `health` thêm vào prefix storefront giữ chỗ.

## 0.3.24 — 2026-10-07

Khai báo dữ liệu plugin + gỡ an toàn (roadmap Phase 5). Thêm + đổi hành vi gỡ.

### Thêm
- Manifest `data` (`owned`, `references`, `retained`) — `Extension\Domain\Plugin\PluginDataDeclaration`, `PluginManifest::$data`. Validate: `owned` là bảng `plg_*`, `retained` ⊆ `owned`, `references` dạng `bang.cot` đi từ bảng `owned`.
- `vani:plugin:uninstall --drop-retained [--yes]`.
- Doctor: `data_undeclared`, `data_owned_missing`, `data_reference_invalid`.
- Khai báo cho `vani.cms`, `vani.hello-world`, `vani.vnpay` (`plg_vnpay_refunds` là `retained`: chứng từ hoàn tiền).

### Đổi hành vi
- `uninstall` từ chối khi implementation còn việc dở dang (trước: gỡ được sau `--force` tắt).
- `uninstall --purge` từ chối khi còn tham chiếu (khai báo hoặc khoá ngoại thật) hoặc dữ liệu `retained` chưa xác nhận.

## 0.3.23 — 2026-10-07

Plugin ngừng an toàn (roadmap Phase 5). Thêm + đổi hành vi CLI.

### Thêm
- Trạng thái plugin `draining`: vẫn nạp provider, vẫn xử lý giao dịch đang dở; không được chọn cho giao dịch mới.
- `Extensions::acceptsNewTransactions(object $implementation): bool`. Core dùng ở phương thức thanh toán (checkout), phương thức giao (`ShippingOptions`), hãng cho vận đơn mới (`CarrierRegistry::acceptsNewShipments`). Plugin có điểm khởi tạo giao dịch riêng nên dùng cùng kiểm tra này.
- `vani:plugin:disable --drain`, `vani:plugin:finish-draining` (lịch 5 phút); doctor báo `draining`.

### Đổi hành vi
- `vani:plugin:disable --force` khi còn việc dở dang: liệt kê và hỏi xác nhận; chạy không tương tác phải kèm `--yes` (trước: tắt ngay).
- Kiểm tra plugin phụ thuộc khi tắt/ngừng tính cả plugin đang `draining`.

## 0.3.22 — 2026-10-07

Đối soát khoản đã thu với cổng (roadmap Phase 3). Chỉ thêm.

### Thêm
- `GatewayStatus::$refunded` (tuỳ chọn, cuối constructor): tổng đã hoàn phía cổng, nếu cổng tra được. Cổng hiện có không phải sửa; muốn đối soát hoàn tiền thì điền trường này trong `query()`.
- `vani:payment:verify [--days=7]` + bảng `payment_reconciliations`/`payment_reconciliation_lines`: ghi chênh lệch (`gateway_not_captured`, `amount_mismatch`, `refund_mismatch`) để xử lý tay, không đổi trạng thái thanh toán.

## 0.3.21 — 2026-10-07

Đối soát event tích hợp ngoài `order.*` (roadmap Phase 4). Chỉ thêm.

### Thêm
- `Payment\Contracts\Payments::settlementsForOrder(int $orderId)`: khoản đã thu (kể cả đã hoàn hết) và hoàn tiền đã xong của đơn.
- `Ordering\Contracts\OrderReader::cancellations(int $orderId)`: các lần huỷ một phần (id, lý do, số tiền, dòng).
- `vani:integration:reconcile-orders` bù cả `order.lines_cancelled`, `payment.captured`/`refunded`, `return.created`/`resolved`, `shipment.status_changed` (chi tiết báo cáo có `entity`). Schema các event này thêm trường tuỳ chọn `reconciled` (không phá vỡ, giữ v1).

## 0.3.20 — 2026-10-15

Bảng đối soát tồn kho (roadmap Phase 3). Chỉ thêm.

### Thêm
- Màn hình Admin "Đối soát tồn kho" (`admin.inventory.reconciliations.index`, `admin.inventory.reconciliations.show`, quyền `inventory.view`): xem phiên + dòng chênh lệch của `vani:inventory:verify` (nội bộ, `source = internal_verify`) và `vani:inventory:reconcile` (nguồn ngoài).
- Lệnh `vani:inventory:reconcile` (`--source`, `--file`/`--json`, `--dry-run`): so `on_hand` với snapshot nguồn ngoài, áp lên VaniShop khi `locations.stock_authority = source`.

## 0.3.19 — 2026-10-15

Chuyển kho có vòng đời `pending → shipped → received`, `cancelled` (roadmap Phase 1). Chỉ thêm.

### Thêm
- Event `Inventory\Events\StockTransferCreated`, `StockTransferShipped`, `StockTransferReceived`, `StockTransferCancelled` (dispatch sau commit; payload: `publicId`, `fromLocationId`, `toLocationId`, `variantIds`; `StockTransferCancelled` thêm `restocked`).
- Permission `inventory.transfer` và màn hình Admin "Chuyển kho" (`admin.inventory.transfers.*`).

## 0.3.18 — 2026-10-04

Huỷ một phần đơn. Chỉ thêm (+ đổi hành vi hoàn tiền trong transaction).

### Thêm
- Event `Ordering\Events\OrderLinesCancelled` (sau commit): `orderId`, `publicId`, `cancellationId`, `lines` (dòng, variant, số lượng, thành tiền phần huỷ), `amount`, `reason`, `source`.
- `InventoryReservation::releaseQuantities(string $key, array $quantities, string $reason)`: nhả một phần hàng giữ.
- `OrderActionRejected::cannotCancelLines()`, `invalidCancelQuantities()`.
- Event tích hợp `order.lines_cancelled` + schema `docs/api/schemas/events/order.lines_cancelled.json`; `vanishop.order.v1.lines` chỉ gồm dòng còn hiệu lực.

### Đổi hành vi
- `PaymentService::refund()` trong transaction của nghiệp vụ gọi: gọi cổng hoàn tiền **sau khi** transaction đó commit (trước: ngay sau transaction của refund — nghiệp vụ gọi rollback thì tiền đã hoàn).

## 0.3.17 — 2026-10-04

Định danh công khai trong event + nhân viên tạo đổi/trả. Chỉ thêm (payload tích hợp: chỉnh v1 trước khi có bên tích hợp).

### Thêm
- Tham số tuỳ chọn cuối constructor (plugin nghe event không phải sửa): `PaymentCaptured::$paymentPublicId`, `RefundCompleted::$refundPublicId`/`$paymentPublicId`, `ReturnRequested::$publicId`, `ReturnResolved::$publicId`/`$number`, `ShipmentStatusChanged::$publicId`/`$trackingNumber`/`$carrierCode`.
- Admin → đơn → "Tạo yêu cầu đổi/trả" (`returns.manage`): nhân viên tạo hộ khách, nguồn `staff`, cùng chính sách/giới hạn số lượng.

### Đổi (payload tích hợp v1, chưa có bên tích hợp)
- `payment.captured`/`payment.refunded`/`return.created`/`return.resolved`/`shipment.status_changed`: id là public id (ULID) thay vì id nội bộ; `return.resolved` thêm `return_number`; `shipment.status_changed` thêm `carrier`, `tracking_number`. Schema `docs/api/schemas/events/*` cập nhật.

## 0.3.16 — 2026-10-03

Củng cố vòng đời plugin. Thêm + đổi hành vi.

### Thêm
- `Extensions::guardDisable(string $tag, callable $check)` và `Extensions::disableBlockers(string $pluginId): list<string>`: module đăng ký kiểm tra "implementation của plugin còn việc dở dang". Core đăng ký cho `vani.payment.gateways` (khoản `pending`/`authorized`) và `vani.shipping.carriers` (vận đơn chưa kết thúc).
- `vani:plugin:disable --force`.
- `ReturnRejected::unknownLines()` (`return.unknown_lines`).

### Đổi hành vi
- `vani:plugin:disable` từ chối tắt cổng thanh toán/hãng vận chuyển còn việc dở dang (trước: tắt được, IPN/webhook sau đó 404). `--force` bỏ qua, ghi audit `forced`.
- Returns `receive()`: khoá tình trạng không thuộc yêu cầu → 422 (trước: bị coi là "bán được" và nhập kho).

## 0.3.15 — 2026-10-03

Cổng VNPay. Chỉ thêm.

### Thêm
- `Payment\Contracts\CallbackResponder` (interface tuỳ chọn của PaymentGateway): `callbackResponse(CallbackOutcome, ?GatewayCallback): array{status, body}` — cổng tự chọn phản hồi IPN cho mọi kết quả (VNPay: luôn 200 + `RspCode`). Không implement → phản hồi mặc định như cũ.
- Enum `Payment\Contracts\Data\CallbackOutcome`: `Applied`, `Duplicate`, `AmountMismatch`, `NotFound`, `Invalid`.
- Plugin `vani.vnpay` (redirect, IPN, querydr, refund).

### Đổi hành vi
- Checkout native: cổng trả hành động `redirect` → chuyển khách sang trang cổng ngay sau khi đặt. Trang đơn đọc trạng thái thanh toán mới nhất (không dùng bản chụp lúc đặt), hiện nút "Thanh toán ngay" (redirect) hoặc mã QR (qr) khi chưa trả, "Đã nhận thanh toán" khi đã trả.
- Callback đã thu tiền trước đó (giao dịch mới cho khoản đã thu) → `Duplicate` (trước: âm thầm bỏ qua với 200 — với cổng không CallbackResponder vẫn là 200 + `acknowledgement`).

### Test
- `migrate:fresh` của test chạy cả migration plugin trong custom/plugin (tránh DDL giữa transaction test trên MySQL).

## 0.3.14 — 2026-10-03

Nội dung: plugin `vani.cms`. Chỉ thêm.

### Thêm
- `PluginServiceProvider::storefrontPages(string $file, ?string $prefix = null)`: plugin xin đoạn đầu URL riêng (vd. `trang`, `tin-tuc`); trùng route Core (`Extension\Application\Storefront\StorefrontPrefixes::RESERVED`) hoặc plugin khác → lỗi lúc boot. Tên route vẫn `storefront.p.{slug}.…`.
- `Storefront\Contracts\SitemapProvider` (tag `vani.storefront.sitemap`): `urls(int $limit): list<string>`, gộp vào `/sitemap.xml`, lỗi chỉ bỏ phần của plugin đó.
- Theme `vani-base`: class `.vani-prose` cho nội dung soạn thảo; Tailwind quét view plugin (`custom/plugin/*/Resources/views`).
- Admin JS: `@admin/http` (`postJson`, `HttpError`) gọi JSON kèm `X-XSRF-TOKEN` (tải ảnh, xem trước).
- Plugin `vani.cms` (trang, tin tức, khối `cms_latest_posts`, Storefront API `/x/vani-cms/*`).

### Sửa
- `ReportPeriod::fromPreset()` lấy giờ theo `CarbonImmutable::now()` (đồng hồ ứng dụng) thay vì giờ hệ thống — test cố định được ngày.

## 0.3.13 — 2026-10-02

Tài khoản khách native + địa chỉ theo danh mục ở mọi nơi. Chỉ thêm.

### Thêm
- Service contract `Checkout\Contracts\ShippingAddresses` (`directory()`, `normalize()`): quy tắc địa chỉ của checkout cho module khác dùng.
- Service contract `Customer\Contracts\CustomerAccounts`: `updateProfile`, `setPassword`, `address`, `addAddress`, `updateAddress`, `deleteAddress`.
- `CustomerRejected::addressInvalid()` (`customer.address_invalid`, 422), `CustomerRejected::phoneInvalid()` (`customer.phone_invalid`, 422), `OrderActionRejected::invalidAddress()` (`order.address_invalid`, 422).
- Native storefront: `/tai-khoan/ho-so` (sửa hồ sơ, đặt/đổi mật khẩu), sổ địa chỉ thêm/sửa/xoá/đặt mặc định (chạy không cần JS).

### Đổi hành vi
- Có danh mục địa giới (`vani.provinces-vn` bật): sổ địa chỉ khách (API `/me/addresses` + native) và Admin đổi địa chỉ đơn bắt buộc mã tỉnh/phường hợp lệ, tên lấy theo danh mục; `province_name`/`ward_name` không còn bắt buộc gửi. Địa chỉ đã lưu với mã cũ vẫn đổi nhãn/mặc định được, chỉ kiểm tra khi đổi tỉnh/phường.
- Số điện thoại sai định dạng trong sổ địa chỉ → 422 `customer.phone_invalid` (trước: lỗi 500 nếu lọt qua validate).

## 0.3.12 — 2026-10-02

Đợt W6b (phần báo cáo) của [extension-surface-v2](extension-surface-v2.md). Chỉ thêm.

### Thêm
- `Extension\Contracts\DashboardWidget` (tag `vani.admin.dashboard.widgets`): `key()`, `label()`, `permission()`, `width()` (1–3), `order()`, `render(): Metric|Series|Table`. Core lọc theo quyền, cô lập lỗi (`Extensions::call`), vẽ theo kiểu dữ liệu; prop `widgets` trên trang Tổng quan. Slot `vani.admin.dashboard.cards` giữ nguyên.
- `Extension\Contracts\ReportProvider` (tag `vani.admin.reports`): `key()`, `label()`, `description()`, `permission()`, `run(ReportPeriod): ReportResult`. Admin → Báo cáo (`/{admin}/reports[/{key}[/export]]`): chọn khoảng thời gian (hôm nay, 7/30 ngày, tháng này/trước, tuỳ chọn), số tổng, biểu đồ, bảng, xuất CSV (BOM UTF-8, chặn công thức). Menu chỉ hiện khi có báo cáo xem được.
- DTO `Extension\Contracts\Data\{Metric, Series, Table, Column, ReportResult, ReportPeriod}` (định dạng `number|money|percent|text`).
- Service contract `Ordering\Contracts\OrderStatistics`: `totals()`, `daily()` (theo múi giờ), `breakdown(SalesDimension)` (thanh toán, kênh, sản phẩm, thương hiệu), `countByStatus()`; DTO `SalesTotals`, `SalesBucket`, enum `SalesDimension`. Doanh thu = đơn không huỷ theo `placed_at`; tối đa 366 ngày.
- `AdminNavigation::add(..., ?Closure $when)`: điều kiện hiển thị thêm cho mục menu.
- Plugin tham chiếu `vani.reports` (5 widget, 5 báo cáo, quyền `reports.view`).

## 0.3.11 — 2026-10-02

Địa giới hành chính Việt Nam. Chỉ thêm.

### Thêm
- `Checkout\Contracts\AddressDirectory` (tag `vani.checkout.address_directories`): có implementation → checkout kiểm tra `province_code`/`ward_code` (issue `address_invalid`), chụp tên chuẩn vào đơn; Storefront API `GET /address/provinces`, `GET /address/provinces/{code}/wards`; checkout native chọn tỉnh/phường.
- Plugin hệ thống `vani.provinces-vn` (bundled): 34 tỉnh/thành, 3.321 phường/xã sau sắp xếp 07/2025.

### Đổi hành vi
- Khi `vani.provinces-vn` bật (mặc định sau `vani:install`), đơn bắt buộc mã tỉnh/phường hợp lệ theo danh mục mới; mã cũ (trước 07/2025) bị từ chối.

## 0.3.10 — 2026-10-02

Đợt W6a của [extension-surface-v2](extension-surface-v2.md): page builder trang chủ. Chỉ thêm.

### Thêm
- `Storefront\Contracts\StorefrontBlock` (tag `vani.storefront.blocks`: `type()`, `label()`, `fields()` (FieldDefinition), `resolve()`, `view()`) + bộ contract test `Storefront\Testing\StorefrontBlockContract`. Core: `hero`, `product_grid`, `brand_grid`, `rich_text`; tham chiếu plugin `vani.hello-world` (`hello_banner`).
- Admin → Giao diện → Trang chủ (`storefront.manage`): thêm/sắp xếp/cấu hình khối, dùng lại mặc định; lưu `core.storefront.home_blocks`. Khối lỗi/plugin tắt bị bỏ khi render.

## 0.3.9 — 2026-10-02

Tài khoản khách native (slice 12b). Chỉ thêm.

### Thêm
- Service contract `Customer\Contracts\CustomerSessions` (OTP đăng nhập, token, địa chỉ) cho bề mặt ngoài Storefront API; middleware `vani.customer-session[:required]` (token trong phiên web).
- `PluginServiceProvider::accountPage($key, $label, $route)` — mục menu tài khoản khách native; trang plugin (`storefrontPages`) chạy cùng phiên khách.
- Slot `vani.storefront.account.menu`, `account.dashboard`, `account.order_detail`.

## 0.3.8 — 2026-10-02

[ADR-031](../19-adr/ADR-031-short-hook-syntax-auto-points.md): cú pháp hook ngắn + điểm mở rộng tự động. Chỉ thêm.

### Thêm
- Helper `vani_filter()`, `vani_action()`, `vani_add_filter()`, `vani_add_action()` — sở hữu plugin suy ra từ vị trí file gọi.
- Khai báo hook theo mẫu (`'<tiền tố>.*'`), thuộc tính `stability` (`stable`|`experimental`), `on_error` (`fail`|`skip`).
- Điểm tự động (`experimental`): `vani.admin.page.*`, `vani.storefront.view.*`, `vani.api.storefront.*`.
- `vani:plugin:hooks` có cột Stability và liệt kê hook cụ thể có listener.

## 0.3.7 — 2026-10-02

Đợt W5b của [extension-surface-v2](extension-surface-v2.md): đăng nhập bằng tài khoản bên ngoài. Chỉ thêm.

### Thêm
- `Customer\Contracts\AuthProvider` (tag `vani.customer.auth_providers`), `AuthProviderFailed`, `Data\ExternalIdentity` + bộ contract test `Customer\Testing\AuthProviderContract`.
- Storefront API: `GET /auth/social`, `POST /auth/social/{provider}/start` (`redirect_uri` theo `VANI_AUTH_REDIRECT_URIS`), `POST /auth/social/{provider}/complete` (`state` một lần, 10 phút) → token Bearer như đăng nhập OTP. Mã lỗi `customer.social_unknown|social_state|social_redirect|social_failed|social_phone_required`.
- Bảng `customer_identities`; hợp nhất khách chuyển danh tính, ẩn danh hoá xoá, xuất dữ liệu có `linked_accounts`.

## 0.3.6 — 2026-10-02

Đợt W5a của [extension-surface-v2](extension-surface-v2.md): plugin làm nền cho plugin, loại plugin, sức khoẻ plugin.

### Thêm
- `PluginServiceProvider::publishHooks($file)` — plugin công bố hook cho plugin khác; tên phải bắt đầu bằng id plugin. Arch test: plugin chỉ dùng `Contracts`/`Events`/`Testing` của plugin đã khai báo trong `requires.plugins`.
- `Extension\Contracts\PluginHealthCheck` (tag `vani.health.checks`) + `Data\HealthStatus`; lệnh `vani:plugin:health` (15 phút/lần, lưu cache cho Admin Plugin); `vani:plugin:doctor` báo `health_warning`/`health_error`.
- `Extensions::kindContract($kind, $tag)`, `kindContracts()`; doctor cảnh báo `kind_mismatch`.

### Phá vỡ (dev)
- Manifest `kind` phải thuộc danh sách chuẩn `PluginManifest::KINDS` (`payment_gateway`, `shipping_carrier`, `shipping_rate`, `tax`, `promotion`, `notification_channel`, `search`, `integration`, `marketing`, `analytics`, `customer_service`, `content`, `theme_extension`, `language`, `feature`); `business` → `feature`/`search`.

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
