# Changelog — public API cho plugin

Ghi mọi thay đổi của extension point public: `Modules\*\Contracts`, `Modules\*\Events`, `PluginServiceProvider`, hook trong `hooks.php`, registry. Nguồn đối chiếu tự động: `tests/Architecture/public-api.snapshot` (test đỏ khi API đổi mà snapshot chưa cập nhật). Chính sách: [extension-model §5](extension-model.md).

> **Giai đoạn dev ([R38](../01-principles/architecture-rules.md), [ADR-048 — sửa đổi 2026-11-20](../19-adr/ADR-048-extension-api-version.md)): nâng cấp Core trước, không cần giữ tương thích ngược với plugin hiện có — plugin cập nhật sau.** Mục có thay đổi phá vỡ ghi thêm **"Plugin cần cập nhật"**; đợt cập nhật plugin ghi lại plugin đã sửa.

Plugin khai báo `requires.vanishop` theo Composer semver. Ở giai đoạn `0.x`, tăng số giữa (`0.1` → `0.2`) được coi là **có thể phá vỡ**, nên `^0.1` không nhận Core `0.2.x`.

## 0.5.79 — 2026-11-25 · Extension API 1.31.0

- Admin: nút Back của trình duyệt khi form còn thay đổi chưa lưu hỏi lại như link của Inertia (`installBackGuard` trong `@admin/forms`, gắn trước Inertia). Backlog L33.
- Lỗi nghiệp vụ không gắn với ô nhập dùng khoá `business` để hiện thành toast: xoá khuyến mãi đã dùng hoặc do plugin quản lý, khôi phục phiên bản menu, sắp xếp ảnh/video sản phẩm, áp file nhập tồn đã hết hạn. Trước đây các lỗi này không hiện trong Admin. Không đổi API cho plugin.

## 0.5.78 — 2026-11-23 · Extension API 1.31.0

- Storefront API v1 thêm `GET /pages/layouts/{surface}` cho danh sách sản phẩm, chi tiết sản phẩm, danh sách thương hiệu và giỏ hàng. Phản hồi trả các vùng theo thứ tự Builder, gồm block của Core và plugin dùng chung slot. Client tiếp tục dùng `GET /pages/home` cho trang chủ.

## 0.5.77 — 2026-11-23 · Extension API 1.31.0

- `CustomerOrders` cung cấp danh mục lý do huỷ cho khách và nhận mã lý do khi huỷ bằng tài khoản hoặc token đơn. Hai endpoint Storefront API nhận `reason_code` và tiếp tục chấp nhận lý do tự do từ client cũ.
- Thư viện ảnh lưu điểm lấy nét cho bản cắt ảnh sản phẩm. Migration thêm hai cột toạ độ có thể để trống vào bảng `media`.

## 0.5.76 — 2026-11-23 · Extension API 1.31.0

- Builder truyền ngữ cảnh công khai của slot qua `ContextualStorefrontBlock` và `BlockContext`. Lưới theo ngữ cảnh lấy sản phẩm từ danh mục, bộ sưu tập hoặc thương hiệu đang xem.
- `FieldDefinition::repeater()` khai báo các trường con của danh sách. Builder kiểm tra và ghi nhận tham chiếu media trong từng mục. `NormalizesBlockConfiguration` cho phép block đọc cấu hình cũ và biên tập theo schema mới. `vani.storefront-extras` 1.5.0 chuyển trình chiếu, lưới banner, tab sản phẩm và mục cam kết sang repeater.
- Builder có khung xem trước block nháp ở các kích cỡ viewport, nhân bản block, xuất và nhập bố cục JSON giữa các trang tương thích.
- `vani.bundles` 1.1.0 cung cấp `GET /api/storefront/v1/x/vani-bundles/bundles` cho danh sách combo đang chạy.
- `vani.cms` 1.6.0 tích hợp banner vào form danh mục qua phần mở rộng Admin có quyền `cms.manage`. Thư viện ảnh có kích thước riêng cho banner desktop, banner mobile và ảnh bìa trang bài viết.

## 0.5.75 — 2026-11-23 · Extension API 1.30.0

- `ManagedPromotions` đồng bộ khuyến mãi do plugin quản lý. `ContextualPromotionAction` nhận ngữ cảnh đầy đủ của giỏ khi tính mức giảm. `PriceResolver` cung cấp giá hiệu lực của các SKU cho plugin.
- `VariantData`, dữ liệu SKU do `VariantDirectory` trả về, thuộc API cho plugin.
- Core có `Carts::addLines()` để thêm nhiều dòng trong một giao dịch. Storefront hỗ trợ form `items[]` và `window.vani.cart.addItems()`.
- Thêm nhiều SKU trả đủ chi tiết qua `changes` và phát `vani:cart-updated` cho từng SKU. Phản hồi vẫn có `change` cho SKU đầu tiên.
- `vani.bundles` dùng các contract trên để bán combo theo số lượng từng sản phẩm và giảm giá khi mua đủ bộ.
- Core lưu cấu hình action theo ngữ cảnh khi ghi nhận lượt dùng. Huỷ một phần đơn sẽ dùng điều kiện combo tại thời điểm đặt.

## 0.5.74 — 2026-11-22 · Extension API 1.29.0

- Gỡ plugin `vani.ghn` (bản mô phỏng GHN) và `vani.sms-brandname` (SMS qua eSMS) vì chưa chốt nhà cung cấp. Extension point `ShippingCarrier`, `ShippingRateProvider`, `NotificationChannel`, `OtpSender` giữ nguyên; contract test vẫn chạy với implementation của Core và plugin khác.
- Cửa hàng đã bật hai plugin: đơn có phương thức giao `ghn` được tạo vận đơn bằng carrier mặc định; OTP chuyển sang kênh kế tiếp (`vani.zalo-zns` hoặc email). Xoá biến `GHN_*` trong `.env` nếu không dùng.
- `vani.phone-otp-login` 1.0.2 cập nhật mô tả kênh gửi mã.

## 0.5.73 — 2026-11-22 · Extension API 1.29.0

- **Builder Engine** (storefront §4): Admin → Giao diện → **Nội dung trang** biên tập block cho mọi surface bằng một màn hình, dùng chung registry, schema, validation và renderer block với trang chủ và trang bộ sưu tập.
- Extension point mới `BuilderSurface` (tag `vani.storefront.builder_surfaces`) và DTO `BuilderZone` (`slot()` / `page()`): plugin gom các slot có sẵn thành một màn hình biên tập, giới hạn loại và số block mỗi vùng. Contract test `BuilderSurfaceContract`.
- Khoá `builder: true` trong `hooks.php` (Core và plugin) đánh dấu slot nhận block; `Hook::acceptsBlocks()`. Block admin đặt vào slot render tại `<x-vani::hook-slot>` sau phần tử của listener `onSlot`, không nhận args của slot. Core mở các slot: `pdp.after_details`, `pdp.after_related_products`, `plp.before_listing`, `plp.after_listing`, `plp.empty`, `cart.empty`, `brands.before_list`, `brands.after_list`.
- Surface Core: `home`, `product`, `listing`, `cart`, `brands`. Trang chủ chuyển sang surface `home`: cấu hình `core.storefront.builder.home`, ảnh đang dùng ghi dưới `storefront.builder` role `home` (migration chuyển dữ liệu cũ).
- **Plugin cần cập nhật**: `PageBlocks::home()`, `saveHome()`, `resetHome()` và `MEDIA_OWNER` được thay bằng `BuilderEngine::blocks()`, `save()`, `reset()`; `PageBlocks::validate()` nhận `BlockLayout`. Route Admin `home-blocks` thành `builder/{surface}`. Test của `vani.cms`, `vani.storefront-extras`, `vani.hello-world` đã cập nhật.

## 0.5.72 — 2026-11-22 · Extension API 1.28.0

- Ordering dùng chỉ mục cho hàng đợi. `OrderActions::cancel()` nhận tùy chọn `reasonCode`. Core đọc thêm mã lý do huỷ qua `vani.order.cancellation_reasons` và thống kê qua `OrderStatistics::cancellations()`.
- `DeliveryStatistics::codAging()` cung cấp tuổi COD chưa khớp bảng kê theo hãng. `vani.reports` 1.3.6 thêm báo cáo COD chờ đối soát và huỷ đơn theo lý do.
- `vani.preorder` 1.1.4 hiện nhãn quá ngày dự kiến trên trang đơn.
- Link đặt lại mật khẩu gắn với địa chỉ email lúc gửi. Link đã gửi theo định dạng cũ cần được yêu cầu lại sau nâng cấp.

## 0.5.71 — 2026-11-22 · Extension API 1.27.0

- Ordering chụp giá vốn từng dòng lúc tạo đơn bằng `vani.pricing.unit_costs`. Listener chạy trong transaction, đọc giá vốn theo lô và trả số nguyên không âm. `OrderStatistics::variantSales()` trả thêm số lượng, doanh thu và giá vốn của các dòng có ảnh chụp.
- `vani.reports` 1.3.5 tính lợi nhuận gộp theo ảnh chụp. Doanh thu của dòng thiếu giá vốn lúc đặt được báo riêng.
- `DeliveryStatistics::cod()` thống kê theo thời điểm giao xong hoặc bắt đầu hoàn về. Bảng kê COD lưu phí hãng thực tế vào từng kiện đã khớp và hỗ trợ bổ sung phí cho kiện đối soát trước đó.
- Inventory có cấu hình tồn an toàn mặc định cho ô kho mới. Ô đã có giữ mức riêng, kể cả mức 0. Form `vani.warehouse` 1.1.1 có danh sách nguồn quản lý và mức ưu tiên, cùng lựa chọn nhập giá trị riêng.
- `vani.cms` 1.5.10 đóng góp nội dung demo và liên kết theo ID trang khi bật. Seeder của Core dùng được khi CMS vắng mặt.

## 0.5.70 — 2026-11-22 · Extension API 1.26.0

- Bộ sưu tập có trang storefront riêng với banner, vùng nội dung trước và sau lưới sản phẩm, cùng API đọc nội dung theo locale.
- `vani.storefront-extras` 1.4.1 trỏ khối Bộ sưu tập nổi bật tới trang mới.

## 0.5.69 — 2026-11-22 · Extension API 1.26.0

- `Inventory\Contracts\StockJournal` đọc biến động của một SKU, có thể lọc theo kho. Plugin Warehouse 1.1.0 dùng contract này cho lệnh `vani:warehouse:stock-card`.
- Storefront khai báo các nhóm middleware dùng cho API và trang plugin. Các helper đăng ký route giữ nguyên chữ ký và thứ tự middleware.
- Beacon lượt xem trình duyệt cùng nguồn nhận `viewer_id` để phân biệt người xem sau cùng một IP. HTML công khai vẫn dùng chung trên CDN.

## 0.5.68 — 2026-11-22 · Extension API 1.25.0

- `Reconciliations::statuses()` đọc trạng thái theo tham chiếu. `matchOpen()` chuyển dòng lệch còn mở sang khớp khi nhập lại bảng kê COD. Dòng đã khớp hoặc đã xử lý thủ công giữ trạng thái hiện tại.
- Thư viện ảnh đọc kích thước WebM từ track video trong 4 MB đầu khi tải tệp lên.

## 0.5.67 — 2026-11-22 · Extension API 1.24.0

- `CustomerProductPrices::forCustomer()` dùng bộ tính giá hiện hành để lấy giá tối thiểu theo sản phẩm cho chính khách nhận nội dung riêng.
- `vani.wishlist` 1.4.0 lưu nhóm giá cạnh mốc giá đã thấy và gửi thông báo theo giá của khách khi đủ điều kiện. Chạy `php artisan vani:plugin:upgrade vani.wishlist` khi nâng cấp cửa hàng đang bật plugin.

## 0.5.66 — 2026-11-22 · Extension API 1.23.0

- `PublicPageExpiry::limit()` cho phép plugin giới hạn thời gian dùng HTML tại CDN khi nội dung có lịch. CMS khai báo mốc bắt đầu và kết thúc của thanh thông báo.
- `CatalogLookup::adminCategories()` tra danh mục theo ID cho form Admin, kể cả danh mục ẩn.
- Công khai DTO `MediaData` của `MediaDirectory::find()` cho plugin cần kích thước và `srcset`.
- Wishlist có trang cho khách vãng lai. Page builder kiểm loại tệp và sự tồn tại của ID đã chọn từ Thư viện ảnh.

## 0.5.65 — 2026-11-21 · Extension API 1.22.0

Học từ Lunar 2.x (step list, modifier theo giai đoạn): ghi rõ và kiểm thật bước nào chạy trong transaction; xem được thứ tự các bước của luồng có điểm mở rộng.

### Thêm
- Khoá `transaction` trong `hooks.php` (`HookDefinition::$transaction`): hook có thể chạy TRONG transaction DB — listener chỉ ghi DB của mình, không gọi mạng, lỗi → rollback. Đã khai cho 18 hook: `vani.cart.validate_line`, `vani.product.before_save`, `vani.product.after_save`, `vani.service.pricing.prices`, `vani.checkout.context`, `vani.checkout.shipping_options`, `vani.checkout.payment_methods`, `vani.checkout.before_validate`, `vani.checkout.after_validate`, `vani.service.checkout.totals`, `vani.service.promotion.result`, `vani.order.before_create`, `vani.order.after_create`, `vani.service.notification.request`, `vani.payment.before_refund`, `vani.service.payment.view`, `vani.returns.before_approve`, `vani.service.returns.view`. `vani:plugin:hooks` thêm cột Transaction. Khoá tự do `runs` (chưa từng được đọc) bỏ.
- Strict (local/test): hook chạy trong transaction của ứng dụng mà chưa khai `transaction: true` → `HookTransactionUndeclared` — cả bộ test giữ khai báo đúng thực tế (transaction bọc test không tính).
- Mọi request qua HTTP client của Laravel đi qua `ExternalCalls::guard('http:<host>')` (`Http::globalRequestMiddleware`): gọi mạng trong transaction chưa commit (listener hook, calculator, validator của plugin) ném `ExternalCallInTransaction` ở dev/test, chỉ ghi cảnh báo ở production. **Plugin cần cập nhật** nếu gọi HTTP trong các điểm trên (plugin trong repo: không plugin nào).
- `vani:flows {flow?}`: các bước của luồng theo thứ tự chạy thật — bước Core, calculator/validator (Core hay plugin, priority), hook kèm listener (priority, plugin đang tắt), cột "Trong transaction"; exit 1 khi bước hook trong transaction chưa khai `transaction: true`. Luồng: `checkout.quote`, `checkout.place_order`. Module mô tả luồng qua service contract `Extension\Contracts\Flows` + `Data\FlowStep` (giữa module, không phải API cho plugin). Thứ tự hook trong mô tả được test đối chiếu với lần xem trước/đặt hàng thật (`CheckoutFlowsTest`).
- `TotalsPipeline::steps()`: một nguồn thứ tự cho cả lúc chạy và `vani:flows`. Doctor `totals_calculator_priority`: calculator của plugin có priority ngoài 300–399 (`TotalsPipeline::PLUGIN_PRIORITIES`) hoặc trùng mã → cảnh báo.
- `HookManager::listeners(string $hook)`: listener theo thứ tự chạy (plugin, priority).
- Tham số tuỳ chọn `permission` cho `adminFormSection`/`adminColumn`/`adminFilter`/`adminTab` (`PluginServiceProvider`, `AdminExtensions`): không có quyền → phần đó không hiện; phần form không bị validate/lưu. Kiểm quyền gom vào một chỗ (thao tác, công cụ dùng chung). `vani.reports` 1.3.4: tab "Kết quả" của chiến dịch dùng `permission:` — nhân viên không có quyền xem báo cáo không thấy tab (trước: tab hiện dòng "Cần quyền xem báo cáo").

### Đối chiếu, không cần làm
- Tồn kho theo sổ biến động, giữ hàng có hạn (`vani:inventory:release-expired` mỗi phút), đối soát (`vani:inventory:verify` hằng ngày), `InventoryStrategy`/`BackorderPolicy`: đã có, tương đương Lunar 2.x. Đơn đã tách trạng thái đơn / thanh toán / giao / đổi trả. Lý do huỷ đơn dạng danh mục: backlog L49.

## 0.5.64 — 2026-11-20 · Extension API 1.21.0 (không đổi)

Sửa 3 test kiến trúc đỏ sau đợt Payment abstraction (0.5.60–0.5.62).

### Sửa
- Hoàn tiền qua cổng (`RefundService`): `ExternalCalls::guard()` đặt ngay trước lời gọi cổng (trong `try`), lỗi guard (`Shared\Support\ExternalCallInTransaction`, kế thừa `LogicException`) được ném lại thay vì bị bắt thành "hoàn thất bại — chờ hoàn tay".
- `storefront.payment.return` (Return URL chung) khai là trang chỉ có trên web: chỉ chuyển hướng tới trang đơn; app hỏi `GET /payments/{payment}`.
- `vani.vnpay` không còn trang storefront riêng → bỏ khỏi danh sách plugin có trang mà không có API.

## 0.5.63 — 2026-11-20 · Extension API 1.21.0

Rà soát kiến trúc 2026-11-20 (đề xuất 1–6, 8) — một đợt việc, một version (quy ước mới). Đánh số lại khi rebase lên 0.5.60–0.5.62 (Payment abstraction).

### Bỏ (phá vỡ) — **Plugin cần cập nhật** nếu dùng các mục dưới (plugin trong repo: không plugin nào dùng)
- Extension point tìm kiếm: `Catalog\Contracts\SearchProvider`, `ConfigurableSearchIndex`, `Data\ProductDocument`, `Catalog\Testing\SearchProviderContract`, `SearchManager`, `ProductDocumentBuilder`, listener `SyncProductSearchIndex`, lệnh `vani:search:reindex`, cấu hình `vanishop.search.provider` / `VANI_SEARCH_PROVIDER`. Dự án chỉ tìm bằng SQL: `Catalog\Application\Search\ProductSearch` (trước là `DatabaseSearchProvider`). Lưu sản phẩm không còn dựng tài liệu tìm kiếm rồi bỏ đi.
- Bỏ `#[PluginApi]` khỏi 22 mục không plugin/theme nào dùng (vẫn là contract giữa module): `Cart\CartRejected`, `Catalog\MediaData`, `Catalog\ProductSearchResult`, `Catalog\SellableVariant`, `Catalog\VariantData`, `Catalog\VariantOptionsText`, `Checkout\CheckoutIssue`, `Checkout\TotalsLine`, `Extension\Formats`, `Fulfillment\AllocationProposal`, `Inventory\IncomingStockData`, `Inventory\ReservedLine`, `Ordering\OrderLineData`, `Ordering\OrderTransitionRejected`, `Ordering\OrderTransitions`, `Payment\PaymentRejected`, `Promotion\AppliedPromotion`, `Promotion\VoucherRejection`, `Promotion\PromotionEngine`, `Promotion\PromotionUnavailable`, `Storefront\ResolvedLink`, `Storefront\LinkResolver`.

### Giữ
- Mọi điểm mở rộng của microkernel (ADR-029) dù chưa plugin nào cài: `ReturnPolicy`, `PricingStrategy`, `InventoryStrategy`, `SourcingStrategy`, `CheckoutValidator`, `TotalsCalculator`, `PromotionAction`, `OrderStatusAction`, `VariantFieldProvider`, `AlertChannel`, `AuthProvider` (+ `AuthProviderFailed`), `Connector`, `InboundHandler`, `CapturesLater` và kiểu dữ liệu trong chữ ký của chúng. API cho plugin: 206 → 184 mục.

### Sửa
- Xuất file sản phẩm không N+1: nạp sẵn trục/giá trị tuỳ chọn; `VariantFieldProvider::values()` được gọi một lần cho mỗi lô 200 sản phẩm (`$styleId` = 0, `$variantIds` thuộc nhiều sản phẩm — đã ghi trong contract). 10.000 sản phẩm / 50.000 SKU: 10.501 truy vấn (+ khoảng 20.000 khi có cột giá) → 551; 8,4 s → 5,7 s.

### Thêm
- `Extension\Application\PendingMigrations`: `/health` có mục `migrations` (degraded khi còn migration chưa chạy), `vani:security:check` báo lỗi, Tổng quan → Việc cần làm "Migration chưa chạy".
- API cho plugin: `SimpleXlsx::read(string $path, int $maxRows = MAX_ROWS)`, `SpreadsheetRows::fromUpload(UploadedFile $file, int $maxRows = …)`, `SpreadsheetRows::csv(string $path, int $maxRows = …)` — thêm tham số tuỳ chọn (minor).
- **Nhập file sản phẩm lớn**: tối đa 60.000 dòng (`ProductFile::MAX_ROWS`, file tới 20 MB; `SimpleXlsx::read()` / `SpreadsheetRows::fromUpload()` nhận trần dòng, trần dung lượng sheet tăng theo). Xem trước nạp sẵn theo lô 1.000 sản phẩm (50.000 dòng: 2,7 s, 140 truy vấn, thay vì ~10 truy vấn mỗi sản phẩm). Quá 200 sản phẩm ghi được → `ApplyProductImportChunkJob` theo lô 100 sản phẩm trong `Bus::batch`; `GET products/import/runs/{run}` (tiến độ, bộ đếm `created`/`updated`/`failed_rows`, tối đa 200 lỗi); trang danh sách sản phẩm hiện tiến độ (`importRunUrl`). Trang xem trước gửi tối đa 1.000 sản phẩm (sản phẩm lỗi trước).
- `Modules\Shared\Support\BulkRun`: việc hàng loạt chạy nền dùng chung (Inventory `StockBulkRun` dùng lại), thêm danh sách lỗi.
- `tests/Architecture/PluginVersionTest.php` + `plugin-versions.snapshot`: plugin đổi code phải tăng `version`. Quy ước: Core tăng version **một lần mỗi đợt việc** ([extension-model](extension-model.md)).
- ADR-007 sửa đổi: phần ERP/Integration đóng băng, không bảo đảm API.

## 0.5.62 — 2026-11-20 · Extension API 1.20.0

Cổng thanh toán nhận đủ ngữ cảnh; Return URL chung trong Core.

### Đổi (phá vỡ)
- `Payments::availableMethods(PaymentContext $context)` thay cho `availableMethods(Money $amount)`. `PaymentContext` thêm `provinceCode`, `wardCode`, `shippingMethod`, `shippingSource`, `itemCount`, `channel` (Checkout dựng từ địa chỉ giao, phương thức giao đã chọn, dòng đơn, nguồn đơn — cổng quyết định COD theo vùng/hãng…).
- `Checkout\Application\PaymentMethods::available(Totals, CheckoutRequest)` (trước: chỉ `Totals`).

### Thêm
- `PaymentData`: `returnUrl` (Return URL chung, null khi không có Storefront), `customer` (người nhận), `clientIp`, `locale`.
- `CheckoutRequest::$clientIp` (Storefront điền IP khách; không tính vào fingerprint idempotency); `Payments::createForOrder(..., ?string $clientIp = null)` lưu `payments.meta.client_ip`, "thanh toán lại" giữ IP.
- Interface tuỳ chọn `HandlesReturn::parseReturn(Request): ReturnResult` + `Contracts\Data\ReturnResult` (`paymentPublicId`, `status: GatewayPaymentStatus`; `ReturnResult::invalid()`); `Payments::resolveReturn(string $gatewayCode, Request)`.
- Route Storefront `GET /_vani/payments/{gateway}/return` (`storefront.payment.return`): về trang đơn kèm thông báo theo kết quả (không ghi nhận thanh toán); không hợp lệ/cổng không hỗ trợ → trang chủ kèm lỗi.

### Plugin đã cập nhật (commit sau, theo 0.5.60–0.5.62)
- `vani.vnpay` 1.1.0: `QueriesStatus`, `RefundsPayments` (`RefundResult`), `HandlesReturn`; bỏ Return URL riêng `/p/vani-vnpay/return` (dùng Return URL chung, `VNPAY_RETURN_URL` vẫn ghi đè); `vnp_IpAddr` = IP khách.
- `vani.vietqr` 0.2.0: chỉ `ReceivesCallbacks`; khoá ký trống → không khả dụng, từ chối mọi callback; bỏ `query()`/`refund()` giả (hoàn tay); bỏ tài khoản giả dự phòng.
- `vani.cod` 1.3.3, `vani.bank-transfer` 1.0.3: bỏ `verifyCallback()`/`query()`/`refund()` thừa.

## 0.5.61 — 2026-11-20 · Extension API 1.19.0

Hoàn tiền qua cổng có ba kết quả, hỗ trợ cổng hoàn bất đồng bộ.

### Đổi (phá vỡ — plugin cổng hoàn tiền cập nhật theo)
- `RefundsPayments::refund()` trả `Contracts\Data\RefundResult` (`outcome: RefundOutcome`, `gatewayReference`, `message`; `RefundResult::succeeded()/pending()/failed()`) thay cho `GatewayResult`. `RefundOutcome`: `Succeeded` (đã hoàn), `Pending` (cổng đã nhận lệnh, tiền về sau), `Failed` (chờ hoàn tay).

### Thêm
- Interface tuỳ chọn `TracksRefunds extends RefundsPayments`: `refundStatus(PaymentData, string $idempotencyKey, ?string $gatewayReference): RefundResult`.
- Lệnh `vani:payment:refunds` (5 phút/lần, `--older-than=10`): khoản hoàn "đang hoàn" quá hạn → cổng `TracksRefunds` tra trạng thái, cổng khác gọi lại `refund()` cùng khoá idempotency. Xong → `completed` + giao dịch `refund`; thất bại → `requested` (audit `payment.refund_gateway_failed`). Ghi kết quả khoá dòng refund — hai lần chạy không chốt hai lần.
- `PaymentGatewayContract`: refund gọi lại cùng key trả cùng kết quả và tham chiếu.

## 0.5.60 — 2026-11-20 · Extension API 1.18.0

Payment abstraction: interface lõi tối thiểu, khả năng là interface tuỳ chọn, trạng thái là enum.

### Đổi (phá vỡ — plugin cổng thanh toán cập nhật theo)
- `PaymentGateway` chỉ còn `code()`, `label()`, `capabilities()`, `isAvailable()`, `initiate()`. `verifyCallback()` → interface tuỳ chọn `ReceivesCallbacks`; `query()` → `QueriesStatus`; `refund()` → `RefundsPayments`. Core kiểm tra bằng `instanceof` (cổng không có callback/tra cứu/hoàn không phải cài method ném lỗi).
- `GatewayCapabilities`: bỏ `callbacks`, `query`, `refund` (suy ra từ interface); còn `partialRefund`, `manualConfirmation`, `paymentTtlSeconds`, `collectsOnDelivery`.
- `CallbackResponder extends ReceivesCallbacks`.
- Enum mới `Contracts\Data\GatewayPaymentStatus` (`Paid`, `Failed`, `Pending`, `Authorized`): kiểu của `GatewayCallback::$status`, `GatewayStatus::$status`. Bỏ hằng `GatewayCallback::PAID/FAILED/PENDING/AUTHORIZED`.
- Enum mới `Contracts\Data\InitiationType` (`None`, `Redirect`, `Qr`, `Instructions`): kiểu của `PaymentInitiation::$type`; hằng `PaymentInitiation::NONE/REDIRECT/QR/INSTRUCTIONS` nay là case enum. JSON `payment.action.type` của Storefront API không đổi.
- `PaymentGatewayContract`: callback/query/refund chỉ kiểm tra khi cổng cài interface tương ứng.

## 0.5.59 — 2026-11-20 · Extension API 1.17.0 (không đổi)

### Thêm
- Cách gợi ý mã sản phẩm đặt được ở Admin → Cài đặt → Cửa hàng: `core.catalog.style_code_prefix` (vd. `SP` → `SP0001`, `SP0002`… theo số lớn nhất của các mã `SP<số>`; trống = tăng số cuối của mã gần nhất như trước) và `core.catalog.style_code_digits` (mặc định 4). `ProductIdentifiers::suggestStyleCode(Settings $settings)`.

## 0.5.58 — 2026-11-20 · Extension API 1.17.0 (không đổi)

Form thêm sản phẩm bớt nhập tay.

### Đổi (Admin)
- Mã sản phẩm: ô để trống khi thêm mới → mã gợi ý (`suggestedStyleCode`: tăng số cuối của mã gần nhất, giữ độ dài, bỏ mã đã có; chưa có sản phẩm → `SP0001`), hiện làm placeholder; gõ chữ thường tự viết hoa.
- Slug: để trống → tạo theo tên (không dấu, trùng thì `-2`, `-3`…); gõ có dấu/khoảng trắng → chuẩn hoá. Slug người dùng gõ trùng sản phẩm khác vẫn báo lỗi.
- Chỉ chọn một danh mục mà chưa chọn danh mục chính → danh mục đó là danh mục chính.
- `Catalog\Application\Products\ProductIdentifiers` (slug không trùng, mã gợi ý) dùng chung cho form, nhân bản, nhập file (bỏ hai bản `uniqueSlug` trùng nhau).

## 0.5.57 — 2026-11-19 · Extension API 1.17.0

Khối lượng mặc định cho sản phẩm chưa nhập khối lượng.

### Đổi
- Cấu hình chuyển sang Catalog: `core.catalog.default_weight_gram` (Admin → Cài đặt → Kho & giao hàng; mặc định `VANI_FULFILLMENT_DEFAULT_ITEM_GRAM`, 300 g) — `Catalog\Contracts\Data\DefaultWeight`. Bỏ `core.fulfillment.default_item_weight_gram` và `FulfillmentSettings::defaultItemWeightGram()`.
- `CatalogReader::sellableVariants()` trả `weightGram` = khối lượng của SKU, chưa nhập thì khối lượng mặc định → phí giao theo cân lúc thanh toán (`TotalsContext::weight()`), đơn tạo trong Admin, file gửi hãng đều dùng. Ô khối lượng của SKU vẫn để trống (không ghi giá trị mặc định vào sản phẩm).
- Nhãn dữ liệu thiếu "SKU thiếu khối lượng" → "SKU chưa nhập khối lượng (đang dùng mặc định)".

## 0.5.56 — 2026-11-19 · Extension API 1.16.0 (không đổi)

### Đổi (Admin)
- Danh sách sản phẩm: dữ liệu còn thiếu hiện bằng icon cảnh báo + số mục (rê chuột xem nhanh) thay cho các nhãn; trang sửa sản phẩm có khung "Sản phẩm còn thiếu dữ liệu" liệt kê từng mục (prop `missing` của `Catalog::Products/Form`).

## 0.5.55 — 2026-11-19 · Extension API 1.16.0

Rút gọn phân quyền (Owner 2026-11-19: phân quyền theo team ở mức module, không giới hạn chi tiết).

### Bỏ (phá vỡ — plugin/test cập nhật theo)
- Phạm vi gán vai trò `owner`/`location`: xoá `Identity\Contracts\Data\ScopeRef`, `Identity\Domain\ScopeType`, `Identity\Contracts\ScopeOptions`; `Authorizer::allows(int $staffUserId, string $permission)` (bỏ tham số phạm vi; `ScopedRbacAuthorizer` → `RbacAuthorizer`). Gọi `Gate::allows('x')` thay cho `Gate::allows('x', [ScopeRef::owner()])`. Bảng `staff_role_assignments` bỏ `scope_type`, `scope_id` (unique `staff_user_id, role_id`; dòng trùng vai trò được gộp). Form Nhân viên chỉ còn chọn vai trò. `StaffUserFactory::withPermissions(array $permissions)`.
- Quyền `settings.secrets`: ô bí mật chỉ `settings.manage` sửa được.
- Trường `contacts_masked` trên dòng khách hàng (giao diện không dùng).

### Đổi
- Mẫu Nội dung bỏ `inventory.view` (Marketing/Nội dung không có quyền kho/giao hàng nào).

## 0.5.54 — 2026-11-19 · Extension API 1.15.0

Phân quyền cho nhiều team (vận hành đơn, marketing, nội dung, owner, phân tích dữ liệu) — giữ RBAC theo phạm vi hiện có (không dùng spatie/laravel-permission).

### Thêm
- **Vai trò mẫu**: `PermissionRegistry` (nay `#[PluginApi]`) có hằng `PRESET_ORDER_OPS|MARKETING|CONTENT|ANALYST`, `register($code, $description, array $presets = [], array $params = [])` (đăng ký lại cùng mã thì gộp mẫu) và `preset($key)`. `PluginServiceProvider::permissions(array $permissions, array $presets = [])`. Admin → Vai trò → Thêm có "Bắt đầu từ vai trò mẫu" (`Identity\Application\RolePresets`). Quyền Core đã khai mẫu; plugin Cms, Lookbook, SizeChart (nội dung), Reports (marketing, phân tích), Warehouse chuyển kho (vận hành đơn).
- **Quyền cài đặt** (`Extension\Contracts\SettingPermissions`): `settings.<nhóm>` cho từng nhóm Cài đặt hệ thống, `settings.plugin.<id>` (tự đăng ký trong `PluginServiceProvider::settings(array $definitions, array $presets = [])`), `settings.secrets` cho ô bí mật; `settings.manage` vẫn bao trùm. Form trả `editable: false` cho ô bí mật khi thiếu quyền. Menu Cài đặt hiện khi sửa được ít nhất một nhóm/plugin. Plugin TrackingPixels, Wishlist, ScarcitySignals, SmsBrandname (marketing), Cms, Lookbook, StorefrontExtras (nội dung), CodRiskGuard, CarrierRouting (vận hành đơn) khai mẫu cho quyền cài đặt.
- **Quyền dữ liệu khách** (`Ordering\Contracts\Data\OrderPermissions`): `orders.export`, `customers.pii`. `Shared\Support\ContactMask` (che tên/SĐT/email). `OrderQueries::detail(..., bool $maskContacts)`, `export(..., bool $maskContacts)`.

### Đổi (phá vỡ — plugin/test cập nhật theo)
- Xuất đơn cần `orders.export` (trước: `orders.view`); in phiếu giao cần thêm `customers.pii`; danh sách/chi tiết đơn và khách che liên hệ khi thiếu `customers.pii` (`contacts_masked` trên dòng khách). `exportUrl` của trang đơn là `null` khi không có quyền xuất; `customerHistory.orders_url` là `null` khi không xem được liên hệ.
- Admin → Cài đặt, trang cài đặt riêng của plugin và nút "Gửi email thử" kiểm quyền theo nhóm/plugin (`settings.<nhóm>`, `settings.plugin.<id>`, `settings.mail`) — `settings.manage` vẫn làm được hết.

## 0.5.53 — 2026-11-19 · Extension API 1.14.0 (không đổi)

Cấu hình nghiệp vụ trước chỉ đặt được bằng `.env` nay chủ shop đổi ở Admin → Cài đặt (namespace `core`; `.env` thành giá trị mặc định khi Admin để trống). Đọc lúc dùng — đổi có hiệu lực ngay, kể cả queue worker đang chạy.

### Thêm (cấu hình `core`)
- **Bán hàng & thuế:** `cart.max_line_quantity`, `cart.max_lines`, `cart.abandoned_after_minutes`, `cart.ttl_days` (`Cart\Contracts\Data\CartSettings`); `pricing.max_discount_percent` (% thay cho basis point — `Pricing\Contracts\Data\DiscountCap`, dùng cho cả khuyến mãi và cảnh báo giá sale); `payment.capture_on` (`shipped` / `manual`).
- **Kho & giao hàng:** `fulfillment.auto_create`, `fulfillment.booking` (`manual` / `on_create`), `fulfillment.default_carrier` (lựa chọn lấy từ các hãng đang bật), `fulfillment.default_item_weight_gram` (`Fulfillment\Application\FulfillmentSettings`).
- **Đơn hàng & đổi trả:** `orders.number_prefix`, `orders.return_window_days`, `orders.confirm_receipt_after_days`, `orders.aged_hours` (trước là hằng 24 giờ — `Ordering\Contracts\Data\OrderSettings`); `returns.attachments_retention_days`.

### Đổi
- Các nơi đọc `config('vanishop.cart.*' | 'payment.capture_on' | 'fulfillment.{auto_create,booking,default_carrier,default_item_weight_gram,return_window_days,confirm_receipt_after_days}' | 'orders.number_prefix' | 'returns.attachments_retention_days' | 'promotion.max_discount_bp')` đọc `Settings` (`core`). Test hoặc plugin đặt các khoá này bằng `config([...])` phải chuyển sang `Settings::set('core', …)`.
- Bỏ hằng `OrderDashboardTasks::AGED_HOURS`.

## 0.5.52 — 2026-11-19 · Extension API 1.14.0

Admin → Cài đặt → **Email gửi đi**: chủ shop tự đặt SMTP và người gửi, có nút gửi thử.

### Thêm
- `SettingGroup::Mail` (nhóm "Email gửi đi", sau "Đơn hàng & đổi trả").
- Contract `Modules\Extension\Contracts\SettingActions`: nút thao tác trên trang một nhóm của Cài đặt hệ thống (`add(SettingGroup, nhãn, route POST)`); trang nhóm nhận prop `actions`.
- Cấu hình `core` nhóm Mail (Notification, `MailSettings`): `mail.transport` (`server` = theo `.env` / `smtp`), `mail.smtp_host`, `mail.smtp_port`, `mail.smtp_encryption` (`tls` STARTTLS / `ssl` / `none`), `mail.smtp_username`, `mail.smtp_password` (secret), `mail.from_address`, `mail.from_name`.
- `POST /admin/notifications/mail/test` (`admin.notifications.mail.test`, quyền `settings.manage`, 6 lần/phút): gửi ngay tới email nhân viên đang đăng nhập, lỗi SMTP báo nguyên văn.

### Đổi
- Mailer mặc định là `vani` (`config/mail.php`): mỗi lần gửi đọc cấu hình trên — SMTP của cửa hàng khi đã chọn SMTP và có máy chủ, không thì mailer `MAIL_MAILER` (`mail.mailers.vani.fallback`); người gửi đã đặt thay ô "Từ" và người gửi phong bì. Đọc lúc gửi nên queue worker đang chạy không cần khởi động lại.
- `vani:security:check`: chỉ báo lỗi email khi chưa đặt SMTP trong Admin **và** `MAIL_MAILER` là `log`/`array`.
- Test đọc thư đã gửi dùng `Mail::mailer('array')->getSymfonyTransport()` (transport của mailer mặc định giờ là `vani`).

## 0.5.51 — 2026-11-19 · Extension API 1.13.0

Admin → Cài đặt tái cấu trúc: "Cài đặt hệ thống" chia nhóm nhỏ, "Cài đặt Plugin" theo từng plugin.

### Thêm
- `Modules\Tenancy\Contracts\Data\SettingGroup` (enum: `Store`, `Appearance`, `Seo`, `Sales`, `Inventory`, `Orders`, `Other`) và tham số `SettingDefinition::$group` (cuối constructor, mặc định `null`). Chỉ dùng cho namespace `core`: cấu hình hệ thống không khai nhóm rơi vào "Khác". Cấu hình của plugin không cần nhóm (mỗi plugin một trang).

### Đổi (Admin)
- `/admin/settings`: hai phần — **Cài đặt hệ thống** (thẻ theo nhóm: biểu tượng, mô tả, vài mục tiêu biểu, số mục, số mục đã chỉnh) và **Cài đặt Plugin** (thẻ theo plugin); ô tìm lọc theo tên nhóm/plugin/mục cấu hình. Props `system`, `plugins` thay cho `cards`.
- Trang nhóm mới `/admin/settings/system/{group}` (route `admin.settings.system`) chỉ hiện cấu hình của nhóm; `/admin/settings/core` chuyển tới nhóm đầu. `/admin/settings/{plugin}` giữ nguyên. Trang chi tiết có cột điều hướng giữa các nhóm và plugin (điện thoại: ô chọn). Props `section`, `active`, `nav` thay cho `title`, `subtitle`.
- Menu "Cấu hình" đổi tên "Cài đặt"; khoá dịch `extension::texts.settings_controller.general_settings`, `platform_setup`, `extension::admin.settings_index.no_settings_registered` bỏ.

## 0.5.50 — 2026-11-19 · Extension API 1.12.0 (không đổi)

Gỡ phần dự phòng cho trình duyệt tắt JS (ADR-025 sửa đổi 2026-11-19). Không đổi API cho plugin.

### Bỏ
- `vani.wishlist` 1.2.0: nút tim là `<button type="button">` trong `[data-vani-wishlist]` (không còn form/token CSRF); bỏ route web `POST /p/vani-wishlist/toggle` và `Wishlists::toggle()`. Thêm/bỏ chỉ qua Storefront API `x/vani-wishlist/items`; trang tài khoản có nút `[data-vani-wishlist-remove]` gọi `DELETE …/items/{style}`.
- Sổ địa chỉ: bỏ `action=reload` (nút `<noscript>` "Tải danh sách phường/xã") ở `AddressRequest` và `AccountSettingsController`; checkout bỏ nút `<noscript>` tương tự (`action=quote` vẫn dùng cho nút "Áp dụng" mã giảm giá và JS tính lại).
- `vani-base`: bỏ nút `[data-no-js]` "Cập nhật" (số lượng) và "Áp dụng lựa chọn" (chọn dòng) ở trang giỏ, nút `<noscript>` "Áp dụng" sắp xếp ở danh sách, CSS `.js [data-no-js]`, khoá dịch `theme::cart.update`, `theme::cart.apply_selection`, `theme::common.load_wards`. Theme con override `partials.cart-page`, `pages.checkout`, `pages.account.address-form`, `partials.listing` nên bỏ các nút này.

## 0.5.49 — 2026-11-19 · Extension API 1.12.0 (không đổi)

Backlog L46 — API-first: so khớp số liệu trang Blade với Storefront API. Không đổi API cho plugin.

### Đổi (theme)
- `vani-base` gắn `data-vani-value` / `data-vani-amount` / `data-vani-code` cho giá, giá gạch, % giảm, còn hàng, dòng giỏ, tạm tính, báo giá checkout, tổng đơn, phân rã giá đơn, trạng thái đơn; thẻ sản phẩm có `data-vani-product`. Theme con override `partials.price`, `partials.product-card`, `partials.cart-page`, `partials.order-summary`, `pages.checkout`, `pages.order`, `pages.product` giữ các thuộc tính này (storefront §5).

### Sửa (Admin, lỗi từ đợt i18n 0.5.43–0.5.45 — 21 test trình duyệt đỏ)
- `t()` gọi trước khi app Inertia khởi tạo làm Admin lỗi JS (`Cannot read properties of undefined (reading 'adminI18n')`) — nay trả bản dịch rỗng, không lỗi.
- `MediaField`, `MediaPicker`, `ExtensionActions` dịch nhãn mặc định trong giá trị mặc định của prop (chạy lúc import) → nút hiện nguyên khoá ("Thêm shared::admin.media_media_field.images"). Nay dịch lúc render; `TranslationKeysTest` cấm dịch trong mặc định của prop.

### Kiểm thử
- `Storefront/Tests/Feature/BladeApiParityTest`: trang sản phẩm ↔ `GET /products/{slug}`, danh mục ↔ `GET /products?category=`, giỏ ↔ `GET /carts/{id}`, thanh toán ↔ `POST /checkout/{cart}/quote`, trang đơn ↔ `GET /orders/{id}`.

## 0.5.48 — 2026-11-19 · Extension API 1.12.0 (không đổi)

Backlog L45 — API-first: Blade và API gọi cùng service tầng Application. Không đổi API cho plugin.

### Sửa
- Storefront API tài khoản đi qua contract như storefront Blade: `PATCH /me`, `PUT /me/password`, `/me/addresses*`, `POST /me/delete`, `GET /me/delete` dùng `CustomerAccounts`; `/auth/*` (đăng ký, xác minh, đăng nhập, quên/đặt lại mật khẩu, đăng xuất, xác minh email) và `/me/email/verification` dùng `CustomerSessions`. Trước đây gọi thẳng `CustomerService`/`AuthService`/`AddressBook`/`AccountLifecycle` — `PATCH /me` bỏ qua hook `vani.service.customer.profile_attributes` (plugin sửa hồ sơ chỉ có tác dụng trên web).
- `vani.storefront-extras`: nhãn tiếng Anh khối "bán chạy" đổi sang "Top selling" (từ cũ nằm trong danh sách ngoài phạm vi ADR-033); trang `vani.hello-world` khai cặp với `GET /x/vani-hello-world/greeting` — hai test kiến trúc đang đỏ trên `main`.
- `FieldDefinition::toArray()`: bỏ kiểm `is_string` thừa (Larastan đỏ trên `main` từ 0.5.46).

### Kiểm thử
- `StorefrontApiFirstTest` L45: mỗi cặp trong `STOREFRONT_API_PAIRS` so service tầng Application/Contracts hai phía dùng; khác nhau phải ghi lý do ở `STOREFRONT_SERVICE_DIFFERENCES`.

## 0.5.47 — 2026-11-19 · Extension API 1.12.0 (không đổi)

Củng cố API-first (stabilization S0.5, S0.6). Không đổi API cho plugin.

### Đổi
- Cặp ghi Blade ↔ Storefront API còn validate tay dùng chung FormRequest: `Customer\Contracts\Requests\{LoginRequest, ForgotPasswordRequest, DeleteAccountRequest}`, `Storefront\Http\Requests\{TrackOrderRequest, CancelOrderRequest, OrderReceivedRequest, RetryPaymentRequest, ReturnShippedRequest, ReturnPhotosRequest}`, `vani.wishlist` 1.1.4 `WishlistItemRequest`.
- Arch test `StorefrontApiFirstTest`: mọi cặp ghi xếp loại (dùng chung FormRequest / không có đầu vào kèm lý do), action không validate tay; mọi trang Blade GET có endpoint API GET tương ứng hoặc ghi lý do chỉ-có-trên-web.

### Sửa
- `POST /api/storefront/v1/me/orders/{order}/cancel`: lý do huỷ nhận tối đa 255 ký tự như form Blade và `POST /orders/{order}/cancel` (trước đây 200).

## 0.5.46 — 2026-11-19 · Extension API 1.12.0

ADR-049 đợt A2 + I5 (kết thúc kế hoạch dịch theo khoá). L2, L9 đóng.

### Đổi (API cho plugin)
- `Extension\Contracts\Data\ReportPeriod::PRESETS`: giá trị là **khoá dịch** (`extension::texts.report_period.*`) thay cho nhãn tiếng Việt — hiển thị bằng `__($label)`.

### Đổi
- Exception của tầng Domain (Core) giữ thông báo nội bộ nguyên văn — Domain thuần không gọi `__()`; tầng Application/Http đổi sang thông báo có khoá dịch như trước. `TranslationKeysTest` thêm luật: không nạp bản dịch lúc boot.
- ~950 chuỗi tiếng Việt viết cứng trong PHP (Core + 25 plugin) → khoá `<namespace>::texts.<mục>.<khoá>` (file `resources/lang/{vi,en}/texts.php`): nhãn cấu hình, quyền, menu, thông báo flash, lỗi trả cho người dùng, nhãn báo cáo, khối trang chủ, tên phương thức thanh toán/giao hàng…
- **Định nghĩa đăng ký lúc boot lưu khoá, dịch lúc hiển thị** (theo ngôn ngữ của người xem): `PermissionRegistry::all()`, `AdminMenuRegistry`, `SettingsAdmin` (nhãn/help/lựa chọn; cấu hình `<khoá>.<locale>` được tham số `:locale`), `FieldDefinition::toArray()`, `AdminExtensions` (cột, lọc, thao tác, tab, công cụ, phần form), `RequiredExtensions`, `NotificationType` (nhãn + mẫu mặc định). Plugin ngoài repo: truyền khoá dịch thay cho câu — câu thường vẫn hiện nguyên văn.
- 13 plugin thêm `translations()` (`vani-bank-statement`, `vani-carrier-routing`, `vani-cod-risk-guard`, `vani-cost-price`, `vani-ghn`, `vani-media-webp`, `vani-promotion-rules`, `vani-reports`, `vani-sms-brandname`, `vani-tax-vn-vat`, `vani-video-youtube`, `vani-vietqr`, `vani-vnpay`); 25 plugin tăng patch.
- View in phiếu giao (`ordering::admin.print`) theo ngôn ngữ Admin (`<html lang>`, nhãn).
- `TranslationKeysTest`: mốc ngoại lệ về 0. Vùng không dịch: CLI, log, health-check, `Testing/`, `hooks.php`, `app/Observability`, Pulse, exception cho dev, `#[Deprecated]`, mẫu `preg_*`, dòng `// i18n-ignore: <lý do>` (dữ liệu, vd. tiêu đề cột của định dạng file nhập/xuất — đổi theo ngôn ngữ sẽ làm hỏng nhập lại file).

### Thêm
- Mẫu mail `en` mặc định cho `order_placed`, `order_cancelled`, `shipment_shipped`, `shipment_delivered` (migration, chỉ thêm khi chưa có) — L2.

## 0.5.45 — 2026-11-19 · Extension API 1.11.0 (không đổi)

ADR-049 đợt A3 (phần 2) — mọi trang Vue của Admin dùng khoá dịch. Không đổi API cho plugin.

### Đổi (Admin)
- Trang Vue của Catalog, Inventory, Ordering, Checkout (tạo đơn), Payment, Fulfillment, Returns, Pricing, Promotion, Customer, Extension, Storefront và plugin `vani.cms`, `vani.lookbook`, `vani.size-chart`, `vani.warehouse` dùng `t('<namespace>::admin.<trang>.<khoá>')`; bản dịch `resources/lang/{vi,en}/admin.php` (~1.550 câu). Chọn English ở dropdown là đổi toàn bộ giao diện Vue.
- `vani.cms`: `kindLabels()` / `statusLabels()` thành hàm (tính theo ngôn ngữ lúc gọi).
- `TranslationParams` nhận `null`/`undefined`/`boolean` (hiện như `{{ }}` của Vue).
- Prop `adminI18n` ≈ 126 KB (vi, trước nén), gửi một lần mỗi ngôn ngữ/version.

## 0.5.44 — 2026-11-19 · Extension API 1.11.0 (không đổi)

ADR-049 đợt A3 (phần 1). Không đổi API cho plugin.

### Đổi (Admin)
- Component dùng chung của Admin (`resources/js/admin`: DataTable, Dialog, Combobox, MultiSelect, Select, Pagination, MediaManager/Picker/Field, SkuPicker, Sortable, FileInput, Toaster, Dashboard, form standard) và trang Vue của Identity, Integration, Notification dùng khoá `t('<namespace>::admin.<trang>.<khoá>')` — file `resources/lang/{vi,en}/admin.php` của từng module (`shared::admin` cho component dùng chung). Giá trị mặc định của prop trong `withDefaults` là factory (`() => t(...)`) để đổi theo ngôn ngữ.
- `toast.ts`, `upload.ts` (module thuần, test bằng node) dịch qua `textTranslator.ts` (`translateText()`); `i18n.ts` nối `t()` khi nạp.

## 0.5.43 — 2026-11-19 · Extension API 1.11.0 (không đổi)

ADR-049 đợt A1 (Admin chọn ngôn ngữ).

### Thêm
- Dropdown ngôn ngữ ở thanh trên Admin và trang đăng nhập/quên mật khẩu/lời mời (`LocaleSwitcher`, ẩn khi cửa hàng một ngôn ngữ). `PUT /admin/ngon-ngu` (`admin.locale.update`): nhân viên lưu `staff_users.locale` (audit `identity.staff.profile_updated`), khách lưu phiên `admin_locale`.
- Middleware `Identity\Http\Middleware\UseAdminLocale` trong nhóm `vani.admin` (trước `HandleInertiaRequests`): hồ sơ nhân viên → phiên → mặc định.
- Bản dịch cho Vue: prop `adminI18n` (once prop, khoá `admin-i18n.<locale>.<version>`) = nhóm `admin` của mọi namespace dịch (`Shared\Support\AdminTranslations`); `resources/js/admin/i18n.ts` có `t(key, params)`, `tc(key, count, params)` cùng cú pháp `__()`/`trans_choice()` (phần thuần ở `translate.ts`, test `npm run test:admin`). Plugin có trang Admin: thêm `Resources/lang/{vi,en}/admin.php`.
- Chuỗi vỏ Admin (`AdminLayout`) → `shared::admin.layout.*`.

### Sửa
- Props dùng chung của Admin (`app.locale`, định dạng tiền theo ngôn ngữ) trước đây luôn theo ngôn ngữ mặc định vì được tính trước khi biết nhân viên — nay theo ngôn ngữ của nhân viên.

## 0.5.42 — 2026-11-19 · Extension API 1.11.0

ADR-049 đợt I4. **Thay đổi phá vỡ cho plugin/theme ngoài repo** (Owner: bỏ JSON ngay, không giữ tương thích): bản dịch `Resources/lang/<locale>.json` (khoá = câu tiếng Việt) không còn được dùng.

### Đổi (plugin)
- 10 plugin chuyển sang khoá `vani-<plugin>::storefront.<khoá>` và `$this->translations($this->pluginPath('Resources/lang'), 'vani-<plugin>')`, file `Resources/lang/{vi,en}/storefront.php`; xoá `en.json`: `vani.cms` 1.5.6, `vani.wishlist` 1.1.2, `vani.lookbook` 1.1.5, `vani.size-chart` 1.0.5, `vani.phone-otp-login` 1.0.1, `vani.storefront-extras` 1.2.2, `vani.scarcity-signals` 1.0.3, `vani.tracking-pixels` 1.0.2, `vani.preorder` 1.1.1, `vani.hello-world` 0.3.1.
- `PluginServiceProvider::accountPage()`: `$label` là **khoá dịch** của plugin (vd. `vani-wishlist::storefront.wishlist`), theme hiện theo ngôn ngữ trang.
- Plugin ngoài repo: đổi `__('Câu')` → `__('vani-<plugin>::<nhóm>.<khoá>')`, thêm `Resources/lang/{vi,en}/<nhóm>.php`, thay `loadJsonTranslationsFrom()` bằng `translations()` ([plugin-system §10 bước 11](../05-plugin/plugin-system.md)).

### Test
- Bỏ `StorefrontI18nTest` — `TranslationKeysTest` thay thế; luật 1 (không dùng câu làm khoá) và luật 4 (không JSON) không còn ngoại lệ.

## 0.5.41 — 2026-11-19 · Extension API 1.10.0 (không đổi)

ADR-049 đợt I3. Không đổi API cho plugin.

### Đổi (theme)
- Theme `vani-base` dùng khoá `theme::<nhóm>.<khoá>` (252 câu, nhóm `common`, `layout`, `catalog`, `product`, `cart`, `checkout`, `account`, `order`, `js`), file `lang/{vi,en}/<nhóm>.php`. Bỏ `lang/en.json` và việc nạp JSON của theme (`UseActiveTheme`). **Theme con** đang ghi đè chuỗi bằng `lang/<locale>.json` phải chuyển sang `lang/<locale>/<nhóm>.php` với cùng khoá (chỉ khai khoá muốn đổi).
- Menu tài khoản của Core (`AccountMenuComposer`): nhãn → `storefront::labels.account_{overview,orders,addresses,profile}`.

### Sửa
- Trang tiếng Việt hiện số sản phẩm bằng tiếng Anh ("1 product", "No products") ở danh mục, thương hiệu, tìm kiếm.

## 0.5.40 — 2026-11-19 · Extension API 1.10.0 (không đổi)

ADR-049 đợt I2. Không đổi API cho plugin.

### Đổi
- Module `Storefront`, `Customer` không còn dùng câu làm khoá dịch: nhãn breadcrumb/tài khoản/ảnh sản phẩm → `storefront::labels.*` (nhóm mới), thông báo 404 của Storefront API → `storefront::messages.{brand,category,product}_not_found`, lỗi số điện thoại địa chỉ → `customer::messages.phone_invalid`. Bản `en` của chúng không còn phụ thuộc `en.json` của theme.

### Sửa
- Storefront API ở `/en` (và `Accept-Language: en`) trả thông báo 404 tiếng Anh thay vì tiếng Việt.

## 0.5.39 — 2026-11-19 · Extension API 1.10.0 (không đổi)

ADR-049 đợt I1 (hạ tầng). Chưa đổi chuỗi nào của theme/plugin.

### Thêm
- Namespace dịch `theme::` (`Storefront\Application\Theme\ThemeTranslationLoader`, bọc `translation.loader`): file `custom/theme/<theme>/lang/<locale>/<nhóm>.php`, gộp theo chuỗi theme cha → con (theme con ghi đè từng khoá); `vani.theme` tính lại chuỗi theme mỗi request.
- Arch test `TranslationKeysTest` (5 luật của ADR-049) với mốc ngoại lệ `tests/Architecture/translation-baseline.php` chỉ được giảm (`VANI_UPDATE_TRANSLATION_BASELINE=1` hạ mốc).

### Sửa
- Bản `en` còn thiếu: nhóm khách và tag (Customer), campaign (Promotion), yêu cầu đổi trả + trạng thái đổi trả ở storefront (`storefront::messages.return_*`) — trang `/en` trước đây hiện tiếng Việt cho các thông báo này.

## 0.5.38 — 2026-11-19 · Extension API 1.10.0 (không đổi)

Backlog L33 (một phần). Không đổi API cho plugin.

### Đổi (Admin)
- Danh sách Nhân viên (tên, đăng nhập gần nhất), Nhật ký gửi tin (thời gian, trạng thái), message lỗi ở Tích hợp (#, số lần thử — cột "Lần thử" riêng) sắp xếp theo cột ở server (`ListSort`), dùng `useListQuery`. Prop mới `sort`.
- Lưới Tồn kho điều hướng (lọc, phân trang) qua `useListQuery`: giữ vị trí cuộn, thay lịch sử, làm mờ lưới khi đang tải. Lưới gộp theo sản phẩm × biến thể nên không sắp theo cột.

## 0.5.37 — 2026-11-19 · Extension API 1.10.0 (không đổi)

Backlog L33 (một phần). Không đổi API cho plugin.

### Đổi (Admin)
- Danh sách Khách hàng (tên, ngày tạo), Vận đơn (COD), Thanh toán (số tiền, ngày tạo), Đổi trả (yêu cầu, tiền hoàn), Khuyến mãi (tên, hiệu lực, đã dùng) sắp xếp theo cột ở server (`sort`/`direction`, `Shared\Http\ListSort`), dùng `useListQuery` (giữ bộ lọc, vị trí cuộn, trạng thái đang tải). Prop mới `sort`.

## 0.5.36 — 2026-11-19 · Extension API 1.10.0

Backlog L37 (Owner: CSKH bấm "Giao trước phần có hàng", COD thu theo giá trị từng kiện).

### Thêm (API cho plugin — minor; phá vỡ nhẹ theo R38)
- `Payments::collectCod(int $orderId, int $amount, string $reference, ?int $expected = null)` — thêm `$expected` (tiền thu hộ của kiện). Không truyền: kỳ vọng = phần còn phải thu (hành vi cũ). Cài đặt `Payments` trong plugin (nếu có) phải thêm tham số.
- `IncomingStock::dueBy(string $date)` — hàng đang về có ngày dự kiến ≤ ngày.
- `ShipmentReader`, `ShipmentView` mở cho plugin (`experimental`).

### Đổi
- COD cộng dồn theo kiện: mỗi kiện giao ghi một `cod_collected`; đủ tổng mới `paid`. Số hãng báo khác số của kiện → "Đối soát lệch".
- `FulfillmentService::split()` + thao tác "Giao trước phần có hàng" trên trang đơn (`POST admin/fulfillment/shipments/{id}/split`, ô `move_<order_line_id>`); lỗi `shipment.split_invalid`.
- `vani.preorder` 1.1.0: chặn đẩy hãng theo từng kiện; ô "Đặt trước quá ngày dự kiến (SKU còn đơn chờ)" ở Việc cần làm.

## 0.5.35 — 2026-11-19 · Extension API 1.9.0 (không đổi)

Backlog L20 (Owner: việc tồn kho hàng loạt lớn chạy nền). Không đổi API cho plugin.

### Đổi
- Nhập file tồn và sửa tồn hàng loạt: quá 1.000 dòng/biến thể → chia lô 500 chạy nền (`Inventory\Application\StockBulkRun`, `ApplyStockChunkJob`, `Bus::batch`, queue `default`) dưới danh tính nhân viên đã bấm; trang Tồn kho (`?run=`) hiện tiến độ và kết quả qua `GET admin/inventory/stock/runs/{run}`. Bỏ trần 5.000 (trần an toàn 50.000 dòng/file, 100.000 biến thể/lần). Trang xem trước file lớn trả tối đa 1.000 dòng, ưu tiên dòng lỗi/thay đổi (`hidden_lines`).
- **Production cần worker queue `default` chạy** (Horizon đã cấu hình) — không có worker thì việc lớn đứng ở 0%.

### Sửa
- MySQL: số bán được (`StockQueries::sellableByVariant` — lưới tồn, Tổng quan) lỗi 1690 "BIGINT UNSIGNED out of range" khi một biến thể có tồn an toàn lớn hơn tồn khả dụng — cả trang Tồn kho trả 500. Điều kiện lọc nay so sánh thay vì trừ.

## 0.5.34 — 2026-11-19 · Extension API 1.9.0

Backlog L44 (Owner: sản phẩm ngừng bán → 410 + gợi ý).

### Thêm (API cho plugin — minor)
- `CatalogReader::discontinuedProduct(string $slug, string $locale): ?array{name, category_slug}` — sản phẩm lưu trữ theo slug.

### Đổi
- `slug_redirects` thêm cột `locale` (migration mới; `''` = slug gốc): slug riêng của bản dịch (`style_translations`, `category_translations`) đổi → link cũ của ngôn ngữ đó 301. `CatalogReader::slugRedirect()` ưu tiên slug của ngôn ngữ đang xem.
- Trang sản phẩm ngừng bán → HTTP 410 + view mới `theme::pages.product-gone` (gợi ý 8 sản phẩm cùng danh mục), `noindex`. Theme con không có view này dùng bản của `vani-base`.

## 0.5.33 — 2026-11-19 · Extension API 1.8.0 (không đổi)

### Sửa
- Outbox: deadlock InnoDB giữa các worker (CI run 44346155925, `OutboxConcurrencyTest` đỏ ~1/12 lần trên MySQL 8.4): `OutboxWorker::reclaimStuck()` chạy `UPDATE … WHERE status = 'processing'` ở mỗi lượt, khoá khoảng chỉ mục `status` và chạm với câu ghi "đã gửi" của worker khác. Nay đọc id không khoá trước, chỉ UPDATE theo khoá chính khi thật sự có message kẹt; `claim()` tự thử lại khi gặp deadlock (3 lần). Lỗi gốc hiện ra dưới dạng `TypeError` vì `Concurrency\ProcessDriver` của Laravel không dựng lại được `QueryException` từ tiến trình con.

## 0.5.32 — 2026-11-18 · Extension API 1.8.0

Backlog L40: xác minh email sau khi đổi email trong hồ sơ. Từ bản này `vanishop.extension_api_stage = dev` (Owner 2026-11-18): API cho plugin chưa chốt, thay đổi phá vỡ chỉ cần tăng minor + ghi ở đây; plugin trong repo cập nhật theo ([ADR-048 — sửa đổi](../19-adr/ADR-048-extension-api-version.md)).

### Thêm (API cho plugin — minor)
- `CustomerSessions::sendEmailVerification(int $customerId): bool`, `CustomerSessions::verifyEmail(string $token): ?CustomerData` — gửi link xác minh email hiện tại của tài khoản (link một lần, hiệu lực `verify_ttl_hours`), bấm link → đã xác minh.
- **Phá vỡ (giai đoạn dev — ADR-048 sửa đổi 2026-11-18):** `CustomerData` thêm tham số constructor `bool $emailVerified = false` (`email_verified` trong `toArray()`). Plugin tự dựng `CustomerData` (fake trong test) không phải sửa vì có giá trị mặc định.
- `CustomerRejected::emailLinkInvalid()` (`customer.email_link_invalid`, 422), `emailAlreadyVerified()` (`customer.email_already_verified`, 409).

### Thêm (Storefront API — không phá vỡ)
- `POST /me/email/verification` (gửi lại link, 202), `POST /auth/email/verify` (`token`) — xác minh, không đăng nhập. `GET/PATCH /me` có `email_verified`; `PATCH /me` đổi email → tự gửi link tới email mới.
- Headless: `vanishop.customer.email_auth.verify_email_url` (`VANI_CUSTOMER_VERIFY_EMAIL_URL`, có `{token}`).

### Đổi (theme)
- Trang Hồ sơ: email chưa xác minh hiện "Email chưa xác minh. Gửi lại link xác minh" (form `storefront.account.email.resend`); link trong email mở `storefront.account.email.verify` (không cần đăng nhập).

## 0.5.31 — 2026-11-18 · Extension API 1.7.0

Backlog L10 (Owner quyết: gợi ý shop chịu phí ship trả khi lỗi do cửa hàng).

### Thêm (API cho plugin — minor)
- `ReturnStatistics::byReason()`: mỗi dòng thêm `store_fault` (lý do "lỗi do cửa hàng") và `store_shipping_fee` (phí ship trả hàng cửa hàng chịu, yêu cầu đã hoàn tất).

### Đổi
- Hoàn tất yêu cầu hoàn tiền (Admin → Đổi trả → chi tiết): thêm phí ship trả hàng + ai chịu (`return_shipping_fee`, `return_shipping_payer` — migration mới). Khách chịu → trừ vào tiền hoàn; cửa hàng chịu → không trừ. Lý do "lỗi do cửa hàng" → mặc định cửa hàng chịu. `ReturnService::resolve()` nhận thêm hai tham số tuỳ chọn.
- `vani.reports` 1.3.1: báo cáo "Lý do trả" có cột Nhóm (Lỗi do cửa hàng / Phía khách), cột và chỉ số "Phí ship trả shop chịu", chỉ số tỷ lệ lỗi do cửa hàng.

## 0.5.30 — 2026-11-18 · Extension API 1.6.0

Backlog L16 (Owner quyết: giữ ảnh đổi trả 90 ngày sau khi yêu cầu đóng).

### Thêm (API cho plugin — minor)
- `OrderReader::customerOrderIds(int $customerId): list<int>` — mọi đơn của một khách (kể cả đơn huỷ), để xoá dữ liệu cá nhân gắn với đơn khi khách bị ẩn danh hoá.
- `ReturnView::$acceptsAttachments` — yêu cầu còn nhận thêm ảnh (chờ duyệt/đã duyệt, chưa đủ 5 ảnh).

### Thêm (Storefront API — không phá vỡ)
- `POST /orders/{order}/returns/{return}/photos` (multipart `photos[]`): khách gửi thêm ảnh cho yêu cầu còn mở; `returns[].can_add_photos` trong đơn. Blade: form "Gửi thêm ảnh" trên trang đơn (`storefront.order.returns.photos`).

### Đổi
- Ảnh đổi trả là dữ liệu cá nhân: lệnh hằng đêm `vani:returns:purge-attachments` (03:45) xoá ảnh (file + dòng) của yêu cầu đã hoàn tất/từ chối/huỷ quá `vanishop.returns.attachments_retention_days` (90, `VANI_RETURN_ATTACHMENTS_RETENTION_DAYS`); khách bị ẩn danh hoá (`CustomerAnonymized`) → xoá ngay ảnh của mọi đơn của khách.

## 0.5.29 — 2026-11-18 · Extension API 1.5.0

Backlog L22.

### Thêm (API cho plugin — minor)
- Domain event `Modules\Tenancy\Events\SettingChanged` (`namespace`, `key`, `value` — null khi xoá hoặc cấu hình `secret`), phát sau commit mỗi lần `Settings::set`/`forget`. Plugin nghe để phản ứng khi cấu hình đổi (`onEvent`, hoặc action `vani.event.tenancy.setting_changed`).

### Sửa
- Ảnh chọn ở cấu hình kiểu `media` (Ảnh thay thế, Ảnh khi chia sẻ link, cấu hình `media` của plugin) nay là "đang dùng" trong Thư viện ảnh — không xoá được; đổi/bỏ cấu hình thì ảnh cũ được thả (Catalog `DeclareSettingMedia`). Cấu hình đã lưu trước 0.5.29 được khai báo ở lần lưu kế tiếp.

## 0.5.28 — 2026-11-18 · Extension API 1.4.0 (không đổi)

Backlog L32. Không đổi API cho plugin.

### Đổi (Admin)
- Form khuyến mãi: danh sách voucher phân trang 50 mã/trang (`voucher_page`) và ô tìm theo mã (`voucher_q`, chuẩn hoá như khi tạo mã) — trước chỉ hiện 200 mã mới nhất, khuyến mãi có hàng nghìn mã riêng cho từng khách thì mã cũ không bật/tắt được. Props mới `voucherFilters`, `voucherPagination`.

## 0.5.27 — 2026-11-18 · Extension API 1.4.0 (không đổi)

Backlog L31: đặt vận đơn không giữ khoá trong lúc chờ hãng. Không đổi API cho plugin (`ShippingCarrier::createShipment` vẫn được gọi một lần cho mỗi lần đặt, nay ngoài transaction).

### Đổi
- `FulfillmentService::book` (nút "Đẩy lên hãng", hàng loạt, job, `ShipmentBooking`): giữ chỗ bằng lease (`shipments.booking_started_at`, `booking_lease`, migration mới) → gọi hãng ngoài transaction → ghi kết quả nếu lease còn của mình. Lần đặt trùng trong lúc hãng chưa trả lời → lỗi `shipment.booking_in_progress` (409). `TransactionBoundaryTest` không còn ngoại lệ nào.

## 0.5.26 — 2026-11-18 · Extension API 1.4.0 (không đổi)

Backlog §6b đợt 1 (L5, L13, L15, L34). Không đổi API cho plugin.

### Đổi
- `Carts::markOrdered` (đặt đơn từ một phần giỏ): các dòng còn lại được **chọn lại** — khách quay lại giỏ là thanh toán được ngay (L5, Owner quyết; trước: giữ trạng thái bỏ chọn).
- Gỡ chuỗi `storefront::messages.return_reasons` không còn dùng (lý do đổi trả lấy từ danh mục) (L13).

### Kiểm thử
- Bộ test chạy `--parallel` không còn phụ thuộc thứ tự (L34): test lệnh `vani:warehouse:reconcile` đăng ký lệnh vào console kernel; `AdminNavigationGroupsTest` kiểm nhóm của mục menu, không kiểm thứ tự (vị trí menu do Owner chỉnh).

## 0.5.25 — 2026-11-18 · Extension API 1.4.0

Storefront S3: SEO và chia sẻ link.

### Thêm (API cho plugin — minor)
- `CatalogReader::slugRedirect(string $subject, string $slug, string $locale): ?string` + hằng `SLUG_PRODUCT`, `SLUG_CATEGORY`, `SLUG_BRAND`: slug hiện tại của bản ghi từng dùng `$slug` (đổi slug ở Admin → bảng `slug_redirects`). Plugin có trang theo slug của Catalog dùng để chuyển hướng 301.
- `CatalogReader::category()` / `categoryTree()` (và Storefront API `GET /categories`, `/categories/{slug}`): thêm `meta_title`, `meta_description`.

### Đổi (theme)
- Layout in thẻ Open Graph/`twitter:card` cho trang công khai được index, mô tả dự phòng = mô tả cửa hàng; trang đặt section `og_image`, `og_type` (theme con/plugin không tự in `og:*`). `theme::pages.category`, `theme::pages.brand` nhận `$meta` (`Storefront\View\PageMeta`); `$page['meta']['image']` ở trang sản phẩm. Trang chủ có JSON-LD `Organization` + `WebSite`.
- `$shell['share']` = `{site_name, description, image, locale, json_ld}`.
- `vani.cms` 1.5.5, `vani.lookbook` 1.1.4: thẻ chia sẻ qua section, không tự in.

### Sửa
- Canonical/hreflang không còn kèm bộ lọc, sắp xếp, `utm_*`; trang 2 trở đi tự canonical (trước: về trang 1). Trang tìm kiếm `noindex`.
- Trang thương hiệu không có mô tả in thẻ `<meta name="description" content="">` rỗng.
- `image` trong JSON-LD `Product` là URL tuyệt đối.

### Cấu hình
- `core`/`storefront.meta_description`, `core`/`storefront.share_image` (Admin → Cấu hình); `vanishop.locale.og_locales`.

## 0.5.24 — 2026-11-18 · Extension API 1.3.0 (không đổi)

Storefront S2: checkout trên điện thoại. Không đổi API cho plugin.

### Đổi (theme)
- `theme::pages.checkout` nhận `$form` (`Storefront\View\CheckoutForm`): `values` (giá trị từng ô — `old`, hoặc điền sẵn từ hồ sơ + địa chỉ mặc định khi khách đăng nhập), `saved_addresses`, `errors` (tóm tắt lỗi). Theme con override trang checkout: đọc `$form['values'][<ô>]` thay cho `old()`.
- Tóm tắt lỗi đầu trang (`[data-checkout-errors]`), thanh đáy điện thoại (`[data-checkout-bar]`), ô lọc phường/xã (`[data-ward-filter]`, cả `theme::pages.account.address-form`), ô "Địa chỉ đã lưu" (`[data-checkout-saved-address]`). Đổi phường/phương thức → tính lại và thay `[data-checkout-summary]`, `[data-checkout-shipping]`, `[data-checkout-payment]`, `[data-checkout-bar]`, sau đó phát `vani:content-updated` (slot của plugin trong các khung này được render lại).

### Sửa
- Lỗi chung của checkout (tổng đã đổi, hết hàng…) không hiện khi tắt JS.
- Đổi phường/phương thức giao không tính lại phí giao (plugin hãng tính phí theo địa chỉ → tổng hiển thị cũ, đặt hàng bị từ chối "tổng đã thay đổi").
- Docblock `CustomerSessions::REQUEST_ATTRIBUTE` ghi giá trị là `CustomerData`; thực tế là model khách (đọc `id`, dùng `Customers::find`).

## 0.5.23 — 2026-11-18 · Extension API 1.3.0 (không đổi)

Storefront S1: rào chắn mobile & tốc độ. Không đổi API cho plugin.

### Đổi (theme)
- Hiệu ứng hiện dần `data-vani-motion*` chạy bằng CSS transition + `IntersectionObserver`, không tải thư viện Motion nữa (JS trang danh sách 210 → 34 KB, chưa nén). Phần tử trong màn hình đầu lúc tải trang hiện ngay (trước đây ẩn tới khi thư viện tải xong — gồm ảnh hero, làm chậm LCP). `window.vaniLoadVendor('motion')` vẫn dùng được. Theme con có CSS riêng cho `[data-vani-motion-pending]`: trạng thái chờ nay là `opacity: 0; transform: translateY(12px)` (fade: chỉ opacity), độ trễ so le qua biến `--vani-motion-delay`.
- `theme::partials.product-grid` nhận `$eager` (số ảnh đầu tải ngay); `theme::partials.product-card` nhận `$loading`. Trang danh sách: 2 ảnh đầu `eager` + `fetchpriority="high"`.

### Kiểm thử
- `tests/Browser/StorefrontMobileBudgetTest`: ở 375px không cuộn ngang, ảnh có `width`/`height`, JS ≤ 64 KB, CSS ≤ 96 KB lúc mở trang, ảnh màn hình đầu của trang danh sách/sản phẩm không lazy, không phần tử màn hình đầu bị ẩn chờ hiệu ứng; 320px không cuộn ngang.

## 0.5.22 — 2026-11-18 · Extension API 1.3.0 (không đổi)

### Sửa
- `vani:plugin:upgrade <id>` khi plugin đã ở version trong code: báo "đã ở version …" và **trả mã 0** (trước là mã 1 — vòng nâng mọi plugin lúc deploy báo lỗi giả, backlog L38). Hạ version, không tương thích, migration lỗi vẫn mã 1. Admin vẫn báo như cũ (`PluginAlreadyCurrent` là một `PluginOperationFailed`).

## 0.5.21 — 2026-11-18 · Extension API 1.3.0 (không đổi)

Theme `vani-base` đợt 2: layout và partial không còn tự tính. Không đổi API cho plugin.

### Đổi (theme)
- View composer mới (`Storefront\View\Composers`): `$shell` cho layout (menu, thanh thông báo, liên hệ/mạng xã hội/phương thức thanh toán/bản quyền ở chân trang, link tài khoản theo khách, ô tìm kiếm, thay đổi giỏ, URL `window.vani`), `$listingView` cho danh sách (link lọc/sắp xếp/phân trang, form khoảng giá), `$sizes` cho `partials.image`, `$productFrame` cho thẻ sản phẩm và giỏ mini, `$notifications` cho thông báo, `$accountLinks` cho menu tài khoản. Bảng biến: [storefront §4c-2](../14-storefront/storefront.md). Theme con override các view này: đọc biến dựng sẵn thay vì gọi `ListingUrl`, `ResponsiveImage`, `StoreClock`, `CartController::CHANGE_FLASH`, `vani_product_ratio()`, `request()`, `session()`.
- Ngày giờ đơn hàng định dạng sẵn: `placed_at_label` (danh sách đơn và chi tiết đơn ở trang tài khoản), `returnForm.deadline_label`.

### Sửa
- Id phần tử thông báo/toast in nguyên chữ `{{ $index }}` (mọi mục cùng id) — nay `vani-notification-0`, `vani-toast-0`…

## 0.5.20 — 2026-11-18 · Extension API 1.3.0 (không đổi)

Theme `vani-base`: tính toán chuyển khỏi template trang sản phẩm và breadcrumb. Không đổi API cho plugin.

### Đổi (theme)
- `theme::pages.product` nhận thêm `$page` (`Storefront\View\ProductPage`): metadata + dự phòng (tiêu đề, mô tả từ mô tả sản phẩm, JSON-LD), breadcrumb, gallery (khung, ảnh phóng to), bộ chọn biến thể (tên/giá trị ô nhập, giá từng SKU dạng HTML) và CSS ẩn/hiện nhóm SKU (mã giá trị thoát đúng cú pháp chuỗi CSS — trước đây `addslashes`). Template không còn gọi `StructuredData::product`, `VariantSelector::for`, `vani_product_ratio()` hay đọc `request()`. Theme con override trang sản phẩm: đọc `$page` thay vì tự tính. [storefront §4c-1](../14-storefront/storefront.md).
- Partial `theme::partials.breadcrumb` vẫn nhận `$crumbs`; Core gắn `$breadcrumb = {items, json_ld}` qua view composer (`Storefront\View\Breadcrumbs`). Theme con override partial: hiển thị `$breadcrumb` thay vì tự dựng. Trang danh mục/thương hiệu nhận `$crumbs` từ controller.
- Mô tả meta trang sản phẩm: thiếu `meta_description` → đoạn đầu mô tả (≤ 160 ký tự) thay vì để trống; không có mô tả → không in thẻ.

## 0.5.19 — 2026-11-18 · Extension API 1.3.0

Stabilization A10 — Báo cáo lợi nhuận gộp và đổi trả.

### Thêm (API cho plugin)
- `Ordering\Contracts\OrderStatistics::variantSales(from, to, timezone)` — số lượng và doanh thu dòng đơn theo SKU, theo ngày (đơn không huỷ; chưa gồm phí giao; hàng trả không trừ).
- `Returns\Contracts\ReturnStatistics` (mới): `byVariant` (số lượng trả, tiền hoàn theo SKU), `byReason` (số yêu cầu, số lượng theo lý do, có nhãn) — yêu cầu tạo trong kỳ, trừ bị từ chối/đã huỷ.
- `Fulfillment\Contracts\DeliveryStatistics` (mới): `cod(from, to)` — vận đơn COD trong kỳ: tổng, đã giao, giao thất bại (đang hoàn/đã hoàn về), tiền COD không thu được.
- Hook filter `vani.pricing.unit_costs` (Pricing): giá vốn đơn vị theo variant id cho báo cáo lợi nhuận gộp; SKU không có giá vốn thì không có khoá.

### Plugin
- `vani.reports` 1.3.0: báo cáo "Lợi nhuận gộp theo ngày", "Lợi nhuận gộp theo sản phẩm" (giá vốn qua `vani.pricing.unit_costs`; SKU thiếu giá vốn ghi rõ và tách doanh thu riêng, không coi giá vốn = 0; chưa có nguồn giá vốn thì báo cần bật plugin Giá vốn), "Đổi trả theo sản phẩm" (tỷ lệ trả so với số bán cùng kỳ), "Lý do trả & giao thất bại" (COD giao thất bại, tiền COD không thu được). Xuất CSV như các báo cáo khác.
- `vani.cost-price` 1.1.0: cấp giá vốn cho `vani.pricing.unit_costs`.

## 0.5.18 — 2026-11-18 · Extension API 1.2.0

Stabilization A9 — Đối soát COD.

### Thêm
- Nhập bảng kê COD của hãng vận chuyển (`Giao hàng → Vận đơn → Nhập bảng kê COD`, module Fulfillment — `CodStatement`, route `admin.fulfillment.cod-statements.*`, quyền `payments.confirm`): file .xlsx/.csv có cột mã vận đơn + tiền thu hộ (+ phí, nếu có; nhận "199.000", "199,000 đ"); xem trước từng dòng (khớp / lệch tiền / không có vận đơn / vận đơn chưa giao / đã đối soát trước đó / lặp trong file / số tiền sai) rồi mới ghi. Áp dụng: dòng khớp → sổ đối soát `matched` (nguồn `cod_statement`, tham chiếu = mã vận đơn) và ghi nhận đã thu nếu khoản COD chưa được ghi (`Payments::collectCod`, idempotent); dòng lệch → hàng đợi "Đối soát lệch" ở trang Thanh toán. Nhập lại cùng file không ghi lần hai; có audit `payment.cod_statement.imported`.
- `Payment\Contracts\Reconciliations::recorded(source, references)` — các tham chiếu đã có trong sổ đối soát của một nguồn (để plugin đối soát xem trước file, đánh dấu dòng đã nhập). Thêm phương thức → Extension API 1.2.0 (tương thích ngược với plugin `^1.x` chỉ gọi contract).

## 0.5.17 — 2026-11-18 · Extension API 1.1.0 (không đổi)

Stabilization A8 — Đơn hàng. Không đổi API cho plugin.

### Thêm
- Tạo đơn trong Admin (`Đơn hàng → Tạo đơn`, module Checkout — `AdminOrders`, route `admin.orders.new*`): tìm SKU đang bán, số lượng, người nhận + địa chỉ theo danh mục địa giới, phí giao, thanh toán; tính tiền qua `Checkout::quote` trên giỏ phía server của form (đồng bộ dòng mỗi lần tính) → `placeOrder` nguồn `admin` (cùng luật giá, khuyến mãi, phí giao, giữ hàng; tổng đổi giữa chừng → báo; khoá idempotency của form chống tạo trùng khi bấm lại).
- Xuất đơn ra Excel theo đúng bộ lọc của danh sách: mỗi đơn một dòng hoặc mỗi dòng SKU một dòng (tối đa 10.000 đơn), ghi audit `orders.exported`.

### Sửa
- Chữ trợ giúp cài đặt "Cho phép khách vãng lai đặt hàng" và `CheckoutRejected::signInRequired` còn nhắc "đăng nhập (OTP)" — nay "đăng nhập" (email mặc định từ 0.5.12).

## 0.5.16 — 2026-11-18 · Extension API 1.1.0 (không đổi)

Stabilization A7 — Kiểm kê nhanh. Không đổi API cho plugin.

### Thêm
- Phiên kiểm kê (`Tồn kho → Kiểm kê`, `StockCountService`, bảng `stock_counts`/`stock_count_lines`, số `KK<yymm>-0001`): quét/gõ SKU (mỗi lần +1, "SKU 12" = +12) hoặc tải file, lưu ngay (nhiều người cùng đếm; ô quét gửi theo hàng đợi nên quét nhanh không mất mã), sửa/bỏ số đếm; mỗi lần ghi số đếm lưu tồn hệ thống lúc đó → "Áp dụng" chỉ ghi phần chênh `số đếm − tồn lúc đếm` (hàng bán trong lúc đếm giữ nguyên; chênh âm quá tồn hiện tại → ghi tới 0), một lần mỗi phiên; SKU chưa đếm không đổi. Đọc SKU/số lượng dùng chung với phiếu nhập (`SkuQuantityRows`).

## 0.5.15 — 2026-11-18 · Extension API 1.1.0 (không đổi)

Stabilization A6 — Nhập hàng (thao tác SME). Không đổi API cho plugin.

### Thêm
- Phiếu nhập nhanh (`Tồn kho → Nhập hàng`, `StockReceiptService`, bảng `stock_receipts`/`stock_receipt_lines`): số phiếu `PN<yymm>-0001`; dòng thêm bằng gõ/quét SKU (Enter, quét lại cộng dồn, "SKU 5"), dán nhiều dòng hoặc tải file (.xlsx/.csv); nháp sửa được (khoá lạc quan) → "Nhập kho" cộng tồn một lần (biến động mới `receive`, `reference` = số phiếu) và trừ hàng đang về (ADR-047); phiếu đã nhập/huỷ không sửa. Đơn mua nhiều bước, nhận một phần, giá vốn để plugin (backlog L41).

## 0.5.14 — 2026-11-18 · Extension API 1.1.0 (không đổi)

Stabilization A5 — Pricing. Không đổi API cho plugin.

### Thêm
- Đổi giá niêm yết theo bộ lọc: danh sách sản phẩm có "Chọn tất cả N sản phẩm khớp bộ lọc" (tới 2000, `GET products/ids`) cho mọi thao tác hàng loạt; "Đổi giá" có bước xem trước (số SKU, khoảng giá cũ → mới, cảnh báo qua hook `vani.pricing.bulk.warnings`, dòng mẫu). Thao tác hàng loạt khai `preview_url` → hộp xác nhận hiện kết quả xem trước (`ConfirmOptions.details`).
- Lịch sử giá theo SKU (`Pricing → Lịch sử giá`): mọi lần đổi giá — người đổi, thời điểm, bảng giá, giá và giá gạch cũ → mới; tìm theo SKU/mã/tên sản phẩm, lọc theo bảng giá. Contract giữa module `Identity\Contracts\StaffNames` (id → tên nhân viên).
- Tổng quan giá ở đầu trang Bảng giá (`PriceOverview`): đợt sale/thành viên đang chạy (đánh dấu sắp kết thúc trong 3 ngày), sắp bắt đầu trong 7 ngày; SKU đang bán chưa có giá niêm yết (mẫu có link sửa sản phẩm); cảnh báo trên giá đang chạy theo nhãn của hook `vani.pricing.bulk.warnings` (vd. "Dưới giá vốn" khi bật `vani.cost-price`), kiểm tối đa 500 giá.

## 0.5.13 — 2026-11-17 · Extension API 1.1.0 (không đổi)

Stabilization A4 — Admin Catalog. Không đổi API cho plugin.

### Thêm
- Danh sách sản phẩm lọc theo danh mục (gồm danh mục con), thương hiệu (cả "chưa có thương hiệu"), bộ sưu tập; thao tác hàng loạt: thêm/gỡ danh mục, đổi/bỏ thương hiệu, thêm/gỡ bộ sưu tập, xoá bản nháp (`ProductBulkEditor`).
- Nhân bản sản phẩm thành bản nháp (`ProductDuplicator`): nội dung, phân loại, thuộc tính, trục tuỳ chọn, SKU mới, ảnh, video; event giữa module `Catalog\Events\ProductDuplicated` — Pricing chép giá niêm yết sang SKU mới. Không chép tồn kho.
- Báo sản phẩm thiếu dữ liệu: nhãn trên từng dòng và bộ lọc "Thiếu dữ liệu" (chưa có SKU, ảnh, danh mục, mô tả; SKU đang bán thiếu khối lượng). Giá thiếu báo ở tổng quan giá (A5).
- Nhập/xuất sản phẩm bằng file (`ProductFile`, .xlsx/.csv): mỗi SKU một dòng, file xuất là mẫu nhập lại; xem trước chưa ghi gì (token 30 phút), ô trống = giữ nguyên, sản phẩm mới là bản nháp; thương hiệu và giá trị tuỳ chọn chưa có thì tạo, danh mục/loại tuỳ chọn phải có sẵn; cột theo SKU của `VariantFieldProvider` (giá niêm yết, giá gạch, giá vốn…) theo quyền sửa. `ProductService::inputFor()` dựng ProductInput từ dữ liệu hiện tại.

## 0.5.12 — 2026-11-17 · Extension API 1.1.0

**Email + mật khẩu là cách đăng nhập mặc định của khách; OTP theo số điện thoại chuyển thành plugin `vani.phone-otp-login`.** Phá vỡ Storefront API v1 ở nhóm `auth/*` (môi trường dev, không giữ tương thích ngược).

### Thêm (API cho plugin, `experimental`)
- `Modules\Customer\Contracts\PhoneOtpLogin` — `requestCode(phone, ip)`, `login(phone, code, device)`: khung OTP + quy tắc tài khoản của Core cho plugin đăng nhập OTP.
- `Modules\Storefront\Contracts\CustomerSignIn` — `signIn(request, token, customerId)`: đưa khách vào phiên web của storefront native (cấp lại phiên, gộp giỏ vãng lai, trả URL quay lại).

### Đổi (Storefront API v1)
- Thêm `POST /auth/register` (202, gửi link xác minh — tài khoản chỉ tạo khi bấm link), `POST /auth/register/verify` (token → tài khoản + phiên), `POST /auth/password/forgot` (luôn 202), `POST /auth/password/reset` (204, thu hồi mọi phiên).
- `POST /auth/login` nhận `email` + `password` (thay `phone`); sai quá 5 lần/phút → `customer.too_many_attempts`.
- Bỏ `POST /auth/otp/request`, `POST /auth/otp/verify` → plugin `vani.phone-otp-login`: `POST /x/vani-phone-otp-login/otp/request|verify`.
- `POST /me/delete` nhận `password` (thay mã OTP `delete_account`); tài khoản chưa có mật khẩu → `customer.password_required`.
- `customer.v1.phone` có thể `null` (tài khoản đăng ký bằng email).
- Đăng nhập mạng xã hội: ghép theo email đã xác minh (tạo tài khoản nếu chưa có), rồi SĐT đã xác minh của khách đang có; mã lỗi `customer.social_phone_required` → `customer.social_email_required`.

### Đổi (hành vi)
- Hồ sơ khách vãng lai cùng email thành tài khoản khi bấm link xác minh (giữ lịch sử đơn). Khách vãng lai đặt hàng với SĐT chưa có hồ sơ và email **đã xác minh** của một tài khoản → đơn vào lịch sử tài khoản đó. Đổi email trong hồ sơ bỏ dấu đã xác minh (cột mới `customers.email_verified_at`).
- Storefront native: trang đăng nhập email + mật khẩu (`window.vani.auth.login`), trang đăng ký, quên/đặt lại mật khẩu; xoá tài khoản nhập lại mật khẩu. Form OTP do plugin gắn vào slot `vani.storefront.account.login`.
- `OtpPurpose::DeleteAccount` không còn được Core dùng (giữ trong enum để không phá API cho plugin).

## 0.5.11 — 2026-11-17 · Extension API 1.0.0 (không đổi)

Kiểm chứng phát hành 0.5.x (V6). Không đổi public API.

### Sửa
- **`migrate:rollback` toàn bộ dừng ở `create_variants_table`** (FK từ `variant_option_values`): `create_option_tables` (ADR-045) có `down()` rỗng. Nay dựng lại cấu trúc màu × size (bảng rỗng); catalog đã có biến thể thì dừng với thông báo — quay lại bằng backup.
- `add_variant_options_to_order_lines` có `down()` thật: đưa lại `color_name` (giá trị trục Màu) + `size_code` (mã trục Size).
- CI: bước từ điển dữ liệu chạy thêm rollback toàn bộ rồi migrate lại trên MySQL 8.4.

## 0.5.10 — 2026-11-17 · Extension API 1.0.0 (không đổi)

Kiểm chứng phát hành 0.5.x (V1, V3) — lần đầu chạy cả bộ test với cache trên Redis. Không đổi public API.

### Sửa
- **`/health` luôn trả 503 khi cache là Redis** (bắt buộc ở production): Redis trả số nguyên về dạng chuỗi nên phép thử cache `=== 1` luôn sai. Phép thử nay dùng chuỗi ngẫu nhiên; nhịp scheduler nhận timestamp dạng chuỗi (trước luôn báo `degraded`).
- **Circuit breaker của Integration không bao giờ mở trên Redis** (cùng nguyên nhân): connector hỏng vẫn bị gọi và tốn lượt thử.
- Từ điển dữ liệu: thêm `customer_notes`, `incoming_stock`, `reconciliation_entries`, cột mới của `styles`.
- Test: `VariantOptionsSnapshotTest` không so thứ tự khoá JSON (MySQL sắp lại); test dùng `travel()` vượt TTL cache quay về store `array` (`TestCase::useArrayCache()`).

## 0.5.9 — 2026-11-17 · Extension API 1.0.0 (không đổi)

Rà soát và sửa lỗi sau đợt củng cố kiến trúc. Không đổi public API.

### Sửa
- **IPN về sau khi khách huỷ đơn bị lỗi 500, tiền không được ghi nhận** (do máy trạng thái 0.5.4 thiếu `cancelled → paid`): nay ghi nhận rồi tự hoàn (`late_payment_after_cancel`) như trường hợp hết hạn.
- **Khoản đã hoàn hết nhận thêm IPN "đã trả" (mã giao dịch khác)**: trước 0.5.4 chuyển ngược về `paid` và phát lại `PaymentCaptured`; 0.5.4 thành lỗi 500. Nay coi là bản trùng — mọi chốt ghi nhận tiền dùng `PaymentStatus::everCollected()`.
- `IncomingStock::set()` không còn ghi đè `created_at` khi cập nhật số hàng đang về.
- `vani:plugin:doctor` cảnh báo `incompatible_extension_api` cho plugin chưa cài viết cho version API cho plugin khác (trước chỉ báo `requires.vanishop`).
- Circuit breaker theo plugin không còn hỏi cache ở mỗi listener slot/filter: giữ trạng thái trong tiến trình 5 giây.
- Theme `vani-base`: thiếu bản dịch tiếng Anh của "Thông báo" (Toast) — `StorefrontI18nTest` xanh lại.
- Test trình duyệt `ProductFormTest` (chuyển sản phẩm đơn ⇄ có biến thể): kiểm hiển thị thay vì có trong DOM (khối dùng `v-show`).

## 0.5.8 — 2026-11-17 · Extension API 1.0.0 (không đổi)

Củng cố kiến trúc — đợt K5 "tài liệu và quyết định". Không đổi code hành vi.

### Thêm
- `modules/<Module>/README.md` cho 18 module: vòng, mức domain, trách nhiệm, bất biến, bảng sở hữu, contract công bố (plugin/experimental/giữa module), event. Arch test D5: README liệt kê đủ bảng sở hữu theo migration.
- Glossary: 13 thuật ngữ vận hành và hợp đồng plugin (chờ xác nhận, đẩy lên hãng, đặt trước, chờ hàng, đối soát lệch, chuyển trả, API cho plugin, experimental…).
- Quyết định C3: giữ `Catalog\Media` và `Storefront\{Theme,Menus,Links,Blocks}` ở Core; `Integration` đóng băng tăng trưởng.

## 0.5.7 — 2026-11-17 · Extension API 1.0.0 (không đổi)

Củng cố kiến trúc — đợt K4/D4 "controller mỏng". Không đổi public API.

### Đổi (nội bộ)
- 25 controller Admin/webhook không còn tự truy vấn: truy vấn đọc chuyển sang `Modules\<M>\Application\AdminQueries` (Catalog, Customer, Fulfillment, Identity, Inventory, Notification, Ordering, Payment, Pricing, Promotion, Returns), controller nhận qua constructor.
- Arch test R9/D4 (`ModuleBoundaryTest`): controller không dùng `::query()`, `DB::`, gọi truy vấn tĩnh trên model. Type-hint model cho route model binding và hàm tiện ích tĩnh của model vẫn được.

## 0.5.6 — 2026-11-17 · Extension API 1.0.0 (không đổi)

Củng cố kiến trúc — đợt K4/D3 "Domain Fulfillment". Không đổi public API.

### Đổi (nội bộ)
- `Fulfillment\Domain`: `AllocationCheck` (phân bổ khớp dòng đơn và hàng đang giữ theo kho), `ShipmentCod` (COD thu ở vận đơn đầu), `BookingRetry` (hết lượt đặt lại), `ShipmentStatus::awaitsBooking()` — unit test không cần DB.
- `FulfillmentService` 426 → 356 dòng; chọn hãng tách ra `CarrierSelection`.

## 0.5.5 — 2026-11-17 · Extension API 1.0.0 (không đổi)

Củng cố kiến trúc — đợt K4/D2 "Domain Returns". Không đổi public API (contract `Returns` giữ nguyên).

### Đổi (nội bộ)
- `Returns\Domain`: `ReturnLimits` (số còn trả được theo dòng, giới hạn tiền hoàn), `ReturnProgress` (`return_status` của đơn), `ReturnCondition` (bán được/hỏng → nhập kho lại), `ExchangeValuation` (giá trị đổi hàng: cùng mẫu giữ giá, khác mẫu bù trừ chia theo tỷ lệ), `ReturnStatus::acceptsAttachments()`/`customerCancellable()` — unit test không cần DB.
- `ReturnService` 589 → 352 dòng; sổ sự kiện, đồng bộ đơn, số đã giao/đang trả, view ở `ReturnRecords`; đổi hàng ở `ExchangeResolution`.

## 0.5.4 — 2026-11-17 · Extension API 1.0.0 (không đổi)

Củng cố kiến trúc — đợt K4/D1 "Domain Payment" ([architecture-consolidation](../19-adr/ADR-048-extension-api-version.md)). Không đổi public API (contract `Payments` giữ nguyên).

### Đổi (nội bộ)
- `Payment\Domain`: máy trạng thái `PaymentStatus::transitionTo()` (chuyển sai bảng → `InvalidPaymentTransition`), câu hỏi nghiệp vụ (`everCollected`, `canRetry`, `canConfirmManually`, `closesWithoutCollection`), `RefundStatus`, `RefundRules::decide()`/`isAutomatic()` + `RefundDecision`, `CodRules`, `PaymentAmount::matches()` — unit test không cần DB.
- `PaymentService` (804 → 295 dòng) chỉ còn chọn cổng, tạo/khởi tạo/thử lại, đọc khoản; ghi nhận tiền ở `PaymentSettlement`, hoàn tiền/chuyển trả ở `RefundService`, sổ giao dịch + view ở `PaymentRecords`.

## 0.5.3 — 2026-11-17 · Extension API 1.0.0 (không đổi)

Củng cố kiến trúc — đợt K4/C1 "API vòng 0 khai báo rõ" ([architecture-consolidation](../19-adr/ADR-048-extension-api-version.md)). API giữa module đổi; bề mặt plugin giữ nguyên.

### Đổi (API giữa module)
- Service contract mới ở vòng 0, implementation đổi tên: `Identity\Contracts\AdminNavigation` (Extension cài đặt bằng `Extension\Application\Admin\AdminMenuRegistry`), `Identity\Contracts\PermissionRegistry` (`InMemoryPermissionRegistry`, hằng `WILDCARD` chuyển lên contract), `Identity\Contracts\ScopeOptions` (`ScopeOptionsRegistry`), `Extension\Contracts\AccountPages` (`AccountPageRegistry`), `Extension\Contracts\PluginViews` (`PluginViewRegistry`). Module dùng contract thay cho class `Application` của vòng 0.
- Gỡ phụ thuộc ngược cuối cùng Identity→Extension (`AdminNavigation`): `MODULE_ORDER_DEBT` rỗng.

### Kiểm tra mới
- `ModuleBoundaryTest` C1: module thương mại chỉ dùng `Contracts`, `Events`, `Facades`, `Domain` của Identity/Extension/Tenancy; `Shared` là shared kernel, dùng được toàn bộ.

## 0.5.2 — 2026-11-17 · Extension API 1.0.0 (không đổi)

Củng cố kiến trúc — đợt K3 "kiểm chứng plugin" ([architecture-consolidation](../19-adr/ADR-048-extension-api-version.md)). Bề mặt plugin không đổi (`plugin-api.snapshot` giữ nguyên).

### Thêm
- **Contract test cho 21 điểm mở rộng còn thiếu (R26)** trong `Modules\<Ctx>\Testing`: `CartLineOptionContract`, `VideoDriverContract`, `ImageFormatContract`, `VariantUsageContract`, `AddressDirectoryContract`, `PhoneNumberPolicyContract`, `AlertChannelContract`, `PluginHealthCheckContract`, `ReportProviderContract`, `DashboardWidgetContract`, `CarrierAssignmentContract`, `OrderRiskSignalContract`, `ConfirmationChannelContract`, `PackingSlipTemplateContract`, `OrderStatusActionContract`, `BackorderPolicyContract`, `StockPageLinkContract`, `RefundPayoutContract`, `PriceRoundingContract`, `BulkPriceModeContract`, `SitemapProviderContract`. Mọi implementation tham chiếu (Core + plugin trong repo) chạy bộ tương ứng; `CheckoutValidatorContract` nhận thêm `totals` tuỳ chọn và nay có Core chạy. Arch test: mỗi interface tự khai `TAG` phải có `<Tên>Contract` và ít nhất một implementation chạy nó.
- **Deprecation**: `#[PluginApi(deprecated: '…')]` (cả class), `#[\Deprecated]` của PHP cho hằng/method (đã gắn `ReportPeriod::TIMEZONE`, `AdminScreen::RESOURCES`), khoá `deprecated` trong `hooks.php`. Plugin nghe hook deprecated → log `vani.deprecation` (một lần mỗi tiến trình); `vani:plugin:doctor` quét tĩnh mã plugin đã cài, cảnh báo `deprecated_usage` kèm file:dòng.
- **Circuit breaker theo plugin** (`PluginBreaker`, cấu hình `vanishop.plugins.breaker`) mở rộng từ `Extensions::call` sang slot UI, filter `on_error: skip` và cột/tab/bộ lọc Admin của plugin: lỗi ≥ 5 lần/phút → bỏ qua plugin 5 phút trên các luồng đó, log lỗi; doctor báo `breaker_open`. Hook giao dịch (validate, action, filter `fail`) không dùng breaker.

## 0.5.1 — 2026-11-17 · Extension API 1.0.0

Củng cố kiến trúc — đợt K2 "hợp đồng plugin" ([ADR-048](../19-adr/ADR-048-extension-api-version.md), [architecture-consolidation](../19-adr/ADR-048-extension-api-version.md)). Từ bản này, **API cho plugin có version riêng** `vanishop.extension_api` (bắt đầu **1.0.0**); version Core vẫn tăng theo từng thay đổi.

### Thêm
- `config('vanishop.extension_api')` = `1.0.0`. Manifest khai **`requires.extension_api`** (semver); `requires.vanishop` thành tuỳ chọn (chỉ khi cần bản sửa lỗi cụ thể của Core). Không tương thích → `incompatible_extension_api` (không cài/bật được; đang bật thì không được nạp, chuyển `failed` như `incompatible_core`). Admin → Plugin hiện "Yêu cầu API mở rộng".
- Thuộc tính **`Modules\Shared\Contracts\PluginApi`** đánh dấu class thuộc API cho plugin (195 class: plugin đang dùng, interface có `TAG`, kiểu kéo theo). `Contracts`/`Events` không đánh dấu là API giữa module — plugin không được dùng (R5), Core đổi được mà không tăng `extension_api`.
- Mức ổn định: `#[PluginApi]` (stable) / `#[PluginApi(PluginApi::EXPERIMENTAL)]`. 23 class mới của EP v3 (X1–X7) và `Campaigns` ở mức **experimental**; hook có `stability: experimental` như trước.
- `tests/Architecture/plugin-api.snapshot` + `PluginApiSnapshotTest`: bề mặt plugin (class đánh dấu, `PluginServiceProvider`, class hỗ trợ, helper `vani_*`, hook public) chụp với version `extension_api`; khi ghi snapshot mới, test tự xếp loại — xoá/đổi phần stable bắt tăng **major**, còn lại tăng **minor**. Test P7: plugin trong repo khai `requires.extension_api` theo major hiện tại.

### Đổi
- 29 plugin trong repo: `requires.vanishop: ^0.5` → `requires.extension_api: ^1.0`.

## 0.5.0 — 2026-11-17

Củng cố kiến trúc — đợt K1 "ranh giới có test" ([architecture-consolidation](../19-adr/ADR-048-extension-api-version.md)). **Phá vỡ** (lên số giữa theo [extension-model §5](extension-model.md)): mọi plugin trong repo đổi `requires.vanishop` sang `^0.5`.

### Phá vỡ
- `Checkout\Contracts\AddressDirectory`, `Checkout\Contracts\ShippingAddresses` → **`Shared\Contracts\AddressDirectory`**, **`Shared\Contracts\ShippingAddresses`** (vòng 0, cùng mẫu `PhoneNumberPolicy`); tag `vani.checkout.address_directories` → **`vani.address.directories`**. Gỡ phụ thuộc ngược Ordering→Checkout, Customer→Checkout (M2). `vani.provinces-vn` 1.1.0.
- Promotion bỏ "kết quả chiến dịch" khỏi trang chiến dịch (prop `report`) — nay là tab "Kết quả" của `vani.reports` 1.2.0 (M3). Promotion không còn đọc `OrderStatistics`.

### Thêm
- Service contract **`Promotion\Contracts\Campaigns`** (`usage(campaignId)`) + `Data\CampaignUsage`: số liệu Promotion sở hữu của chiến dịch (đơn đã áp khuyến mãi, tổng giảm, lượt dùng, mã bảng giá) để plugin báo cáo ghép với `OrderStatistics`. Tài nguyên Admin `campaign` (tab của plugin trên trang chiến dịch).
- `VariantDirectory::styleIdsOf(variantIds)`: SKU → sản phẩm, một truy vấn nhẹ (Pricing thôi join bảng `variants`, R11).
- `CustomerSessions::REQUEST_ATTRIBUTE` (khách đã nhận diện của request); `Customer\Contracts\Requests\{AddressRequest, PasswordRequest, ProfileRequest}` — hợp đồng đầu vào dùng chung Storefront API và Blade (ADR-044 §2), trước ở `Customer\Http\Requests` (Storefront không còn dùng tầng `Http` của Customer).

### Kiểm tra mới
- `tests/Architecture/ModuleBoundaryTest.php`: M4/R7 (mọi module chỉ dùng `Contracts`/`Events` của module thương mại khác, mọi tầng kể cả `Http`, quét cả file route/hook) — thay khối R5 "module downstream" cũ; M1 (chỉ dùng `Contracts` của module đứng trước trong `config/modules.php`, nợ còn lại: Identity→`AdminNavigation`, xử lý ở K4); M5/R11 (chỉ module/plugin sở hữu bảng mới truy vấn bảng đó).

## 0.4.69 — 2026-11-16

Đo và tối ưu thao tác Admin một ngày làm việc (1.000 sản phẩm, 3.000 SKU, 120 đơn); sửa áp giá cả bảng bị cắt âm thầm.

### Sửa
- **Áp giá cả bảng bỏ sót SKU mà vẫn báo thành công**: phạm vi "cả bảng"/"sản phẩm đã chọn" bị cắt về 2.000 SKU đầu (`BulkPricesRequest::variantIds`) — bảng sale 3.000 SKU chỉ đổi giá 2.000 SKU. Nay lấy đủ mọi SKU; giới hạn `PriceListWorkspace::MAX_BULK` nâng 2.000 → 5.000 (bằng sửa tồn hàng loạt) và vượt mức thì báo lỗi `scope` kèm số SKU (`pricing::messages.bulk_too_many`), không đổi giá nào.

### Hiệu năng (không đổi hợp đồng)
- `VariantDirectory::find/ofStyles/findBySkus` đọc thẳng bằng query builder, dựng `VariantData` không qua model (cùng kết quả: tên theo locale có fallback, tuỳ chọn theo thứ tự trục, thứ tự SKU theo vị trí giá trị). Trước đó `find()` 3.000 SKU mất 1,8 s, phần lớn là dựng model. `ofStyleCode`/`search` nạp quan hệ dùng chung (`VariantOptions::load`).
- `PriceListService::setPrices` ghi theo lô (upsert + insert lịch sử 500 dòng/lần) thay cho 3 truy vấn mỗi SKU; lịch sử giá, audit, `PriceChanged` giữ nguyên.
- Trang làm việc bảng giá: đếm sản phẩm bằng một truy vấn; hook `vani.pricing.bulk.warnings` chỉ gọi khi có plugin nghe (`Hook::hasListeners`, thêm vào docblock facade).
- Kết quả đo (MySQL, máy dev): tổng thời gian chờ máy chủ trong ngày 154,5 s → 56,4 s; danh sách sản phẩm 271 → 93 ms; đổi giá niêm yết 200 sản phẩm 4,6 s → 0,37 s (2.070 → 27 truy vấn); áp giá sale 200 sản phẩm 4,2 s → 0,3 s; trang làm việc bảng sale 2,0 s → 0,39 s; xuất file tồn 3,0 s → 0,49 s.

### Thêm
- `tests/Performance/AdminWorkflowBenchmarkTest.php` (nhóm `performance`, không chạy mặc định): dựng dữ liệu cỡ thật qua service/API, đo 30 thao tác (ms, SQL ms, số truy vấn, KB, lần/ngày), ghi `storage/logs/admin-benchmark.md`, chặn hồi quy số truy vấn. Chạy: `DB_CONNECTION=mysql DB_DATABASE=vanishop_bench php -d memory_limit=3G vendor/bin/pest tests/Performance --group=performance`.

## 0.4.68 — 2026-11-16

Kiểm soát tiền khi chuyển trả/đối soát, tương thích plugin khi nâng Core, đồng bộ tài liệu, license.

### Sửa
- **Chuyển trả hai lần** (`RefundPayout`): hai lần bấm "Chuyển trả qua …" cùng lúc trước đây đều gọi kênh. Nay Core giành khoản hoàn bằng UPDATE có điều kiện `requested → paying_out` trước khi gọi; lỗi → trả về `requested`; tiến trình chết giữa chừng → khoản hoàn hiện "Đang chuyển trả" ở trang Thanh toán, nhân viên kiểm tra ngân hàng rồi bấm "Đã chuyển trả" (`completeManualRefund` nhận cả `paying_out`). Hợp đồng `RefundPayout` ghi rõ: implementation phải dùng `RefundData::$refundPublicId` làm khoá idempotency phía ngân hàng/ví.
- **Số tiền lệch chỉ ghi log**: cổng/hãng báo thu (hoặc giữ) số tiền khác khoản thanh toán → nay thêm dòng lệch vào hàng đợi "Đối soát lệch" (`Reconciliations`, source `payment`, idempotent theo khoản + số tiền). Trước đây COD hãng thu thiếu làm khoản thanh toán treo "chờ thu" mà không ai thấy.
- **Plugin không tương thích Core vẫn được nạp**: dựng cache plugin (`vani:plugin:cache`, sau mọi thao tác vòng đời) nay chuyển plugin đang bật mà Core đã ra ngoài `requires.vanishop` sang `failed` (`incompatible_core: …`), kéo theo plugin phụ thuộc (`dependency_unavailable: …`), audit `extension.plugin.failed` ([plugin-system §6](../05-plugin/plugin-system.md)).

### Tài liệu
- `status`, `roadmap` đồng bộ Core 0.4.67/0.4.68; EP v3 X1–X7 đánh dấu xong; plugin-catalog thêm `vani.video-youtube`; rà soát hợp đồng plugin ([plugin-system §6.1](../05-plugin/plugin-system.md)).
- License: `composer.json` `"license": "proprietary"` (trước ghi MIT), README trỏ tới `LICENSE.md`.

## Trước 0.5.0

Các bản 0.1–0.4.67 (2026-09 → 2026-11-16, trước khi API cho plugin có version riêng — [ADR-048](../19-adr/ADR-048-extension-api-version.md)) đã gỡ khỏi file này ngày 2026-11-20 cho gọn: plugin trong repo đều theo API 1.x, giai đoạn `dev` không giữ tương thích với API cũ. Xem lại: `git log -p -- docs/04-extension/CHANGELOG-extension.md` hoặc bản file tại commit `ca587cf7`.
