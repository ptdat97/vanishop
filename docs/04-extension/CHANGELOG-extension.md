# Changelog — public API cho plugin

Ghi mọi thay đổi của extension point public: `Modules\*\Contracts`, `Modules\*\Events`, `PluginServiceProvider`, hook trong `hooks.php`, registry. Nguồn đối chiếu tự động: `tests/Architecture/public-api.snapshot` (test đỏ khi API đổi mà snapshot chưa cập nhật). Chính sách: [extension-model §5](extension-model.md).

Plugin khai báo `requires.vanishop` theo Composer semver. Ở giai đoạn `0.x`, tăng số giữa (`0.1` → `0.2`) được coi là **có thể phá vỡ**, nên `^0.1` không nhận Core `0.2.x`.

## 0.4.16 — 2026-11-12

Hoàn thiện API-first (stabilization-2026-11 P0: S0.1–S0.4). **Phá vỡ** hợp đồng Storefront API v1 ở định dạng dữ liệu.

### Đổi (phá vỡ)
- Mọi thời điểm trong Storefront API là ISO-8601 theo múi giờ cửa hàng (vd. `2026-11-12T11:29:00+07:00`): `placed_at`, `timeline[].at`, `shipments[].events[].at`, `returns[].created_at` (trước là chuỗi hiển thị `dd/mm/yyyy HH:ii`); `receipt.confirmed_at`, `returnable.deadline`, `payment.expires_at`, `me/export` (trước là ISO theo UTC); `me/consents[].granted_at|revoked_at` (trước là `Y-m-d H:i:s`). Client tự định dạng hiển thị. Đóng backlog L29.
- Map theo khoá luôn là object JSON, kể cả khi rỗng (`{}` thay vì `[]`): `me/prices.data.prices|products`, `products.meta.facets.color_families`, `order.returnable.lines`. Đóng backlog L30.
- DTO `Ordering\Contracts\Data\OrderDetail::$placedAt`, `$events[].at`, `$receipt['confirmed_at']`, `Returns\Contracts\Data\ReturnView::$createdAt`, `Returns::returnable()['deadline']`, sự kiện của `ShipmentReader`, `Payment` `PaymentView::$expiresAt` cùng theo ISO-8601 giờ cửa hàng; người hiển thị (Blade, Admin) định dạng bằng `StoreClock::format()`.

### Thêm
- `StoreClock::iso()`: thời điểm cho hợp đồng dữ liệu (ISO-8601 theo múi giờ cửa hàng).
- `tests/Architecture/storefront-api.snapshot`: bề mặt hợp đồng Storefront API (thao tác, mã trạng thái, từng trường response). Đổi hợp đồng phải tăng version và ghi mục ở đây (`StorefrontApiSnapshotTest`).
- `Modules\Extension\Testing\StorefrontApiContract` (S0.4): plugin có `storefrontRoutes()` phải có `openapi.json` ở thư mục plugin và test đối chiếu route (`assertRoutesDocumented`) + response thật (`assertResponse`) — arch test đỏ nếu thiếu ([plugin-system §10b](../05-plugin/plugin-system.md#10b-storefront-api-first-cho-plugin-adr-044)). `vani.cms` 1.5.2: `published_at`, `updated_at` theo ISO-8601 giờ cửa hàng.
- `StorefrontApiContractTest` gọi thật mọi thao tác trong OpenAPI (thêm đổi trả, đã nhận hàng, thanh toán lại, đăng nhập mật khẩu, huỷ đơn trong tài khoản, xoá tài khoản); thao tác chưa có response thật được kiểm → đỏ.

## 0.4.15 — 2026-11-12

Khoá hợp đồng HTTP của Storefront API và quy ước API-first cho plugin (ADR-044, api-first-refactor đợt A4–A5). Không đổi hành vi.

### Thêm
- OpenAPI 3.1 `docs/api/openapi/storefront-v1.json` cho 55 thao tác `/api/storefront/v1` của Core, kèm JSON Schema `docs/api/schemas/storefront/*.json`. Từ bản này, đổi/xoá trường JSON hoặc thêm endpoint mà không cập nhật OpenAPI làm đỏ `StorefrontApiContractTest`, và được ghi ở đây như thay đổi contract PHP.
- Quy ước cho plugin ([plugin-system §10b](../05-plugin/plugin-system.md#10b-storefront-api-first-cho-plugin-adr-044), đợt A5): plugin có `storefrontPages()`/`accountRoutes()` phải có `storefrontRoutes()` cho cùng dữ liệu — arch test đỏ nếu thiếu (ngoại lệ có lý do: `vani.vnpay`).
- Ghi nhận giới hạn hiện tại (backlog L29, L30): một số trường thời gian là chuỗi hiển thị chứ chưa phải ISO-8601; map rỗng trả `[]`. Client nên chấp nhận cả hai cho tới khi sửa.

## 0.4.14 — 2026-11-12

Một bề mặt JSON: JS của theme và plugin lấy dữ liệu từ Storefront API bằng phiên web cùng nguồn (ADR-044 §4, api-first-refactor đợt A3).

### Thêm
- Middleware `vani.first-party-session` (`Shared\Http\Middleware\FirstPartySession`) ở đầu nhóm route Storefront API (cả `x/<plugin>`): request cùng nguồn (`Sec-Fetch-Site: same-origin`, hoặc `Origin` trùng host) không mang `Authorization` → chạy phiên web; request ghi phải kèm CSRF. `vani.customer` nhận khách từ phiên khi không có Bearer (token phiên hết hạn = chưa đăng nhập, không 401).
- `POST /api/storefront/v1/products/views` (`style_id`, miễn CSRF, 204) — thay `/_vani/xem`.
- `GET /me/prices` nhận thêm `style_ids[]` → `data.products` (khoảng giá theo sản phẩm khi cửa hàng ẩn giá với khách chưa đăng nhập); `Cache-Control: private, no-store` — thay `/_vani/gia`.
- `vani.wishlist` 1.1.0: `GET x/vani-wishlist/ids`, `POST x/vani-wishlist/items/merge`.

### Gỡ (phá vỡ)
- `GET /_vani/gia`, `POST /_vani/xem`, `GET /p/vani-wishlist/ids`, `POST /p/vani-wishlist/merge`. `/p/vani-wishlist/toggle` chỉ còn là form khi tắt JS. `/_vani/phien` giữ nguyên (CSRF, flash của Blade khi cache CDN); `prices_url` trong dữ liệu phiên trỏ `GET /me/prices`.

## 0.4.13 — 2026-11-12

"Vỏ" trang qua Storefront API (ADR-044, api-first-refactor đợt A2).

### Thêm
- Storefront API `GET /store`, `GET /menus/{location}`, `GET /pages/home` ([api §3](../06-api/api.md)).
- `Storefront\Application\StoreProfile` (use-case dùng chung cho `GET /store` và layout Blade); `PageBlocks::resolve()` trả dữ liệu khối, `render()` dựng HTML từ chính dữ liệu đó.

### Việc của plugin `StorefrontBlock`
- Không đổi contract. Dữ liệu trả từ `resolve()` nay cũng tới client qua `GET /pages/home`: chỉ trả dữ liệu công khai, HTML (`Htmlable`) đã làm sạch — được đổi thành chuỗi.

## 0.4.12 — 2026-11-12

Giỏ hàng: một hợp đồng đầu vào cho Storefront API và Blade; arch test giữ "có trên Blade thì có trên API" (ADR-044, R37 — đợt A1 xong).

### Đổi
- Storefront API `POST /carts/{id}/lines`: `quantity` tuỳ chọn, mặc định 1 (như Blade); thiếu `variant_id` báo "Vui lòng chọn màu và size.".
- Blade `POST /gio-hang/chon` nhận thêm `mode` (`select`/`deselect`) như API; form cũ (`lines[]`) vẫn là `mode=only`.
- `tests/Architecture/StorefrontApiFirstTest.php`: route ghi mới của storefront Blade phải có endpoint `/api/storefront/v1` tương ứng; các cặp đã gom phải dùng cùng FormRequest.

## 0.4.11 — 2026-11-12

Sổ địa chỉ, hồ sơ, mật khẩu khách: một hợp đồng đầu vào cho Storefront API và Blade (ADR-044 §2, đợt A1) — `Customer\Http\Requests\{AddressRequest, ProfileRequest, PasswordRequest}`.

### Đổi
- Storefront API `POST/PATCH /me/addresses`: cửa hàng **không** có danh mục địa giới → nhận tên tỉnh/phường (mã để trống), như form Blade; trước đây API luôn bắt mã nên cửa hàng nhập tự do không lưu được địa chỉ qua API.
- `PUT /me/password`: khách đã có mật khẩu mà không gửi `current_password` → `validation.failed` ở trường đó (trước: `customer.password_mismatch`); gửi sai vẫn là `customer.password_mismatch`.
- Form địa chỉ Blade: SĐT sai báo ở trường `phone` (trước: lỗi chung).

## 0.4.10 — 2026-11-12

Yêu cầu đổi/trả của Storefront API và form Blade dùng một hợp đồng đầu vào (ADR-044 §2, đợt A1).

### Đổi (phá vỡ)
- Storefront API `POST /orders/{id}/returns`: nhận thêm `resolution` (`refund`/`exchange`, tuỳ chọn); yêu cầu có dòng đổi hàng thì mọi dòng phải có `exchange_variant_id` — trước đây API cho trộn dòng đổi với dòng hoàn tiền, form Blade thì không. Lỗi `validation.failed` ở `lines`.
- Form đổi trả của Blade validate bằng cùng `ReturnRequestForm` (form được đổi sang dạng của API trước khi validate).

## 0.4.9 — 2026-11-12

Checkout của Storefront API và form Blade dùng một hợp đồng đầu vào (ADR-044 §2, đợt A1).

### Đổi
- Storefront API `POST /checkout/{cartId}/quote|orders`: địa chỉ thiếu `province_code`/`ward_code` nhưng có tên → mã lấy từ tên đã chuẩn hoá (cửa hàng không có danh mục địa giới), như form Blade vẫn làm. Contact/địa chỉ được cắt khoảng trắng.
- Form checkout Blade validate bằng cùng `CheckoutRequestForm` với API (trường plugin `extra.*.*` nhận chuỗi/số/đúng sai như API). Chuyển sang đăng nhập khi cửa hàng tắt mua không đăng nhập là middleware `vani.checkout-sign-in` của route Blade (chạy trước validate).

## 0.4.8 — 2026-11-12

Mã giảm giá lưu theo giỏ — một mô hình cho mọi kênh (ADR-044 §5, đợt A1 của [api-first-refactor](../20-roadmap/api-first-refactor.md)).

### Thêm
- `Carts::setVoucherCodes(CartKey $key, array $codes): CartView`, hằng `Carts::MAX_VOUCHER_CODES = 5`; `CartView::$voucherCodes` (tham số cuối, mặc định rỗng). Cột `carts.voucher_codes`. Đặt đơn từ giỏ thì xoá mã; gộp giỏ khi đăng nhập thì giỏ đích chưa có mã nhận mã của giỏ nguồn.
- Storefront API `PUT /carts/{id}/vouchers`; giỏ trả `voucher_codes`.

### Đổi (phá vỡ)
- `CheckoutRequest::$voucherCodes` thành `?array`: `null` = dùng mã trên giỏ (mọi kênh storefront truyền `null`), danh sách = tính thử với đúng các mã đó.
- Storefront API `POST /checkout/{cartId}/quote` và `/orders` không còn nhận `voucher_codes` — đặt mã bằng `PUT /carts/{id}/vouchers`.
- Storefront Blade bỏ mã giảm giá trong phiên (`CartVoucher::SESSION_KEY`); ô mã ở giỏ và ở checkout ghi vào giỏ.

## 0.4.7 — 2026-11-11

Mã giảm giá không dùng được không chặn đặt hàng ở mọi kênh (ADR-044 §6, đợt A1 của [api-first-refactor](../20-roadmap/api-first-refactor.md)).

### Đổi (phá vỡ)
- `Checkout::placeOrder()` không còn ném lỗi `checkout.voucher_invalid` khi có mã bị từ chối: mã bị bỏ, đơn đặt theo tổng không có mã đó; kết quả có `dropped_vouchers[{code, reason}]`. Khách đã thấy tổng có giảm mà mã hết lượt sau đó → `checkout.totals_changed` (409) như mọi lần tổng đổi.
- Xoá `CheckoutRejected::vouchers()` và chuỗi `checkout::messages.voucher_invalid`.
- Storefront API `POST /checkout/{cartId}/orders`: mã bị bỏ trả ở `meta.dropped_vouchers`.

## 0.4.6 — 2026-11-11

Drag & Drop Engine dùng chung của Admin (bổ sung ADR-039).

### Thêm
- Component UI công khai cho trang plugin: `@admin/Components/Sortable.vue` (kéo-thả danh sách dọc, lưới, cây nhiều cấp; sắp xếp bằng bàn phím) và `@admin/Components/SortHandle.vue` (tay nắm). Cách dùng ở đầu file `Sortable.vue`.

### Đổi
- Trang plugin không tự viết kéo-thả hay nút Lên/Xuống nữa — arch test chặn; `vani.cms`, `vani.lookbook`, `vani.size-chart` đã chuyển sang `Sortable`.

## 0.4.5 — 2026-11-11

Sửa tồn ngay trong ô ở danh sách sản phẩm Admin (backlog L26).

### Thêm
- Slot `vani.admin.product.list_columns`: listener được trả thêm `urls` (style id => link của ô) và `edit_values` (style id => dữ liệu sửa trong ô, hình dạng do cột tự định nghĩa). Cột `stock` của Inventory trả `{url, location_id, rows: [{variant_id, label, on_hand}]}` khi cửa hàng có đúng một kho do VaniShop quản lý tồn và nhân viên có quyền `inventory.adjust`; Admin lưu qua endpoint lưu nhiều ô của lưới tồn (không ghi đè ô đã đổi sau khi mở trang).

## 0.4.4 — 2026-11-11

Lọc và sắp xếp sản phẩm theo **giá niêm yết** ở trang danh sách (backlog L8, phần giá; Owner chọn giá niêm yết cho giai đoạn SME).

### Thêm
- `Modules\Catalog\Contracts\ListPrices::setMinimums(array $minimumByStyle)`: Pricing ghi giá niêm yết thấp nhất của sản phẩm (bảng giá `base`) sang Catalog — cột `styles.list_price_min`. Chỉ là bản sao cho tìm kiếm; giá hiển thị và giá tính tiền vẫn do `PriceResolver` quyết định.
- `ProductSearchQuery::$priceMin`, `$priceMax`, hằng `SORT_PRICE_ASC`, `SORT_PRICE_DESC`, `SORTS`; `ProductFilters::$priceMin`, `$priceMax`; `ProductDocument::$listPriceMin` (`list_price_min`); `ProductSearchResult::$facets['price']` = `{min, max}` hoặc null.
- Storefront API `GET /products?price_min=&price_max=&sort=price_asc|price_desc` (số tiền theo đơn vị nhỏ nhất); `meta.filters` có mục `price` kiểu `range` kèm `range: {min, max}`. Facet `price` trong Admin → Catalog → Bộ lọc.
- Lệnh `vani:pricing:sync-list-prices` (chạy lại an toàn, theo lịch 03:10 hằng ngày). **Sau khi nâng cấp chạy một lần** để điền giá cho sản phẩm đang có; từ đó giá đổi được đồng bộ theo event `PriceChanged`.

### Lưu ý
- Cửa hàng ẩn giá với khách chưa đăng nhập (`storefront.price_visibility = members`): khách vãng lai không lọc/sắp xếp theo giá được và không nhận khoảng giá.
- Sản phẩm chưa có giá niêm yết bị loại khi lọc giá và xếp cuối khi sắp theo giá.

### Việc của plugin `SearchProvider`
- Lọc theo `list_price_min`, sắp theo giá, trả `facets['price']`. `vani.search-meilisearch` 0.3.0 đã hỗ trợ: chạy `php artisan vani:search:reindex --setup` một lần.

## 0.4.3 — 2026-11-11

Lọc sản phẩm theo size ở trang danh sách (backlog L8, phần size).

### Thêm
- `ProductSearchQuery::$sizeCodes`, `ProductFilters::$sizeCodes` (tham số cuối, mặc định rỗng): sản phẩm có biến thể **đang bán** thuộc một trong các mã size; nhiều size là OR, với nhóm lọc khác là AND.
- `ProductDocument::$sizeCodes` (`size_codes` trong `toArray()`): mã size của các biến thể đang bán.
- `ProductSearchResult::$facets['sizes']` (tuỳ chọn): mã size => số sản phẩm. Provider không trả khoá này thì storefront không hiện bộ lọc size.
- Storefront API `GET /products?size=S,M`, `meta.facets.sizes`; facet `size` trong Admin → Catalog → Bộ lọc (mặc định bật, thêm cuối danh sách đã lưu).

### Việc của plugin `SearchProvider`
- Provider có chỉ mục ngoài cần lọc + trả facet theo `size_codes`. `vani.search-meilisearch` 0.2.0 đã hỗ trợ: chạy `php artisan vani:search:reindex --setup` một lần để cập nhật thuộc tính lọc và index lại.

## 0.4.2 — 2026-11-11

Hàng đợi "Sự cố giao hàng" của Admin gồm cả vận đơn lỗi (trước chỉ có đơn đã hoàn về) — backlog L25.

### Thêm
- `OrderTransitions::setDeliveryIssue(int $orderId, ?string $deliveryIssue, string $reason, string $source)`: Fulfillment ghi sự cố giao hàng đang mở của đơn (`booking_failed`, `failed_attempt`, `returning`; null = hết). Cột `orders.delivery_issue`; mỗi lần đổi có dòng `order_events` loại `delivery_issue_changed`. Service contract do Core implement — plugin không phải sửa.

### Đổi
- Giai đoạn vận hành `issue` đổi nhãn "Hoàn về" → "Sự cố giao hàng" và gồm đơn có `delivery_issue`. Plugin hãng vận chuyển không phải làm gì: trạng thái vận đơn chuẩn hoá (`ShipmentStatus`) đã đủ để Core tính.

## 0.4.1 — 2026-11-11

Chốt ranh giới vòng 0 theo [commerce-kernel §10](../02-architecture/commerce-kernel.md); không đổi hành vi với plugin.

### Thêm
- Class hỗ trợ ngoài `Contracts`/`Events` mà plugin được dùng nay thuộc public API và có trong snapshot: `Modules\Shared\Support\{StoreLocale, StoreClock, MoneyFormatter, LocalizedUrls}` và `Modules\Extension\Facades\Hook`. Arch test R5 chuyển sang danh sách cho phép: plugin chỉ tham chiếu `Contracts`, `Events`, `Testing`, `Shared\Domain`, `PluginServiceProvider` và các class trên.
- Cấu hình cửa hàng `vanishop.storefront.sources`: danh sách nguồn đơn nhận qua header `X-Vani-Source` (mặc định `web`, `app`, `zalo`; giá trị lạ → mục đầu tiên).

### Đổi
- Helper `vani_customer`, `vani_image`, `vani_media`, `vani_srcset`, `vani_product_ratio`, `vani_link` chuyển sang `helpers.php` của module sở hữu contract (Customer, Catalog, Storefront). Tên và chữ ký giữ nguyên.
- Đoạn đầu URL storefront bị giữ chỗ cho Core lấy từ `vanishop.locale.paths` thay cho danh sách ghi cứng: đổi đường dẫn Core trong cấu hình thì plugin tự bị chặn dùng đường dẫn đó.
- Nhóm menu Admin `sales`, `catalog`, `marketing`, `content` do module sở hữu khai báo; khoá nhóm và thứ tự không đổi.

## 0.4.0 — 2026-11-09

Thay đổi không tương thích của API hook.

### Đổi
- Xoá helper bí danh `add_hook_slot()`. Dùng `vani_add_slot()` để đăng ký listener slot.
- Xoá các helper bí danh `hook_filter()`, `hook_action()`, `add_hook_filter()` và `add_hook_action()`. Dùng lần lượt `vani_filter()`, `vani_action()`, `vani_add_filter()` và `vani_add_action()`.
- Sắp xếp lại menu Admin theo luồng làm việc: Tổng quan, Bán hàng, Sản phẩm, Marketing, Báo cáo, Nội dung & giao diện, Hệ thống, Plugin và Mở rộng. Thư viện ảnh thuộc nhóm Nội dung & giao diện; Plugin là mục độc lập. Mục `adminMenu()` không chỉ định thứ tự mặc định chuyển xuống cuối menu.

## 0.3.105 — 2026-11-08

Mở rộng các vị trí UI public của theme `vani-base`, giữ nguyên hook-slot hiện có.

### Thêm
- 19 slot storefront stable cho header/main/footer, trang chủ, thao tác thẻ sản phẩm và cuối danh sách, giỏ hàng, tóm tắt checkout, các trang tài khoản, danh sách thương hiệu và tra cứu đơn. Danh mục và tham số được mô tả ở [extension-point-catalog §4.1](extension-point-catalog.md).

### Đổi
- Slot `vani.storefront.layout.head` xuất trực tiếp `Htmlable`/`SlotView`, không bọc trong `<div>` không hợp lệ bên trong `<head>`.

## 0.3.104 — 2026-11-08

Mã giảm giá không áp dụng được không còn bị giữ lại (native storefront).

### Đổi
- `CartVoucher::preview()` tự bỏ mã khỏi phiên khi không còn áp dụng được (giỏ đổi, mã hết hạn/hết lượt/tắt) — trả null, không còn `rejection`; thêm `CartVoucher::rejectionFor()`.
- Checkout `vani-base`: mã điền sẵn từ giỏ mà không hợp lệ → bỏ lặng lẽ; mã khách vừa nhập rồi "Áp dụng" mà không dùng được → không giữ trong ô, một dòng nhắc nhẹ kèm lý do (`$voucherNotice`) thay cho lỗi đỏ "Mã … không áp dụng được."; đặt hàng với mã không hợp lệ còn trong form → bỏ mã, vẫn đặt đơn (tổng khách thấy vốn không gồm phần giảm). Storefront API giữ nguyên lỗi `checkout.voucher_invalid` cho headless.

## 0.3.103 — 2026-11-08

Danh sách sản phẩm cho vận hành: giá + tồn ngay trên danh sách, mở/ngừng bán và đổi giá hàng loạt.

### Thêm
- Slot `vani.admin.product.list_columns` (cột thêm, `values` style id ⇒ chữ) và `vani.admin.product.bulk_actions` (POST `{ids}` style id) cho module Core. Pricing góp cột "Giá bán" (khoảng giá theo size, giá gạch) + "Đổi giá"; Inventory góp cột "Tồn bán được" (hết hàng / sắp hết).
- Mở bán / Ngừng bán hàng loạt (`POST {admin}/catalog/products/bulk-status`): `ProductService::changeStatus()` — cùng hệ quả lưu form (lock_version, hook `vani.product.after_save`, audit `catalog.product.status_changed`, event ProductUpdated/ProductArchived).
- Đổi giá niêm yết hàng loạt (`POST {admin}/pricing/base-prices/bulk`, `Pricing\Application\BulkBasePrice`): đặt giá mới, giảm/tăng %, giảm số tiền (làm tròn 1.000 ₫), bỏ giá gạch; tuỳ chọn hiện giá cũ gạch ngang khi giảm. Ghi qua PriceListService (lịch sử giá, audit, PriceChanged).
- `VariantDirectory::ofStyles(list<int>)` → `style id ⇒ list<VariantData>`.
- Thao tác hàng loạt (`ExtensionAction`) nhận `data` — giá trị cố định gửi kèm.

## 0.3.102 — 2026-11-08

Đổi trả ít bước: thao tác ngay trên trang đơn và hàng loạt.

### Thêm
- Thẻ "Đổi/trả" trên trang đơn có thao tác theo từng yêu cầu: Duyệt / Từ chối (lý do) → Khách đã gửi hàng → Đã nhận hàng (còn bán được, nhập lại kho; hàng hỏng khai ở trang yêu cầu) → Hoàn tất & hoàn tiền (số tiền điền sẵn) hoặc Hoàn tất & tạo đơn đổi; bước tiếp theo là nút ở đầu trang đơn. Nhãn trạng thái tiếng Việt thay mã thô.
- Danh sách đổi/trả: thao tác hàng loạt "Duyệt" (`POST {admin}/returns/returns/bulk-approve`), "Đã nhận hàng (còn bán được)" (`bulk-receive`); yêu cầu không đúng trạng thái được bỏ qua.

## 0.3.101 — 2026-11-08

"Việc cần làm" đầu trang tổng quan Admin (SME: một màn hình biết hôm nay cần làm gì).

### Thêm
- Slot `vani.admin.dashboard.tasks` (vòng 0): ô hàng đợi `{key, label, count, url, tone?, hint?, order?}` — bấm vào tới danh sách đã lọc; listener tự kiểm quyền.
- Core góp ô: Ordering (cần xác nhận, để quá 24 giờ, chờ thanh toán, chờ giao hãng, hoàn về), Returns (chờ duyệt, đã nhận hàng chờ hoàn tất), Fulfillment (giao không thành công; đặt vận đơn lỗi khi có), Payment (hoàn tiền chờ chuyển trả), Inventory (hết hàng, sắp hết — biến thể đang bán, nhớ 5 phút; `StockGrid::attentionCounts()`).
- Danh sách đơn: bộ lọc `aged` (giờ) — đơn chờ xác nhận / chờ giao hãng đặt quá N giờ.

## 0.3.100 — 2026-11-07

Fulfillment ở lại Core, làm gọn cho SME bằng giao diện ([ADR-043](../19-adr/ADR-043-fulfillment-stays-in-core.md)).

### Đổi
- Menu Admin "Giao hàng" chỉ hiện khi có hãng tự đặt vận đơn qua API (`ShippingCarrier` có `autoBooking`) hoặc từ 2 kho đang giao đơn online; cấu hình `vanishop.fulfillment.admin_menu` (`VANI_FULFILLMENT_MENU` = `auto` | `always` | `never`). Trang vận đơn vẫn mở được từ trang đơn (link trên thẻ Giao hàng).

## 0.3.99 — 2026-11-06

Đổi trạng thái đơn bằng ô chọn (kể cả ngoài trình tự) và hàng loạt — luôn bắt buộc lý do.

### Thêm
- Thẻ "Trạng thái đơn": ô chọn trạng thái + lý do (`POST {admin}/orders/orders/{order}/status`). Mỗi lựa chọn: theo trình tự (lệnh thường, quyền `orders.manage` / huỷ `orders.cancel`), **ngoài trình tự** (chỉ nhảy tới trước, quyền mới `orders.override_status`) hoặc bị chặn kèm lý do (lùi trạng thái, mở lại đơn đã kết thúc, huỷ khi hàng đã rời kho).
- Đổi trạng thái hàng loạt ở danh sách đơn (`POST {admin}/orders/orders/bulk-status` `{ids, status, reason}`): mỗi đơn đi đúng đường, đơn bị chặn/thiếu quyền được bỏ qua.
- Event `Ordering\Events\OrderStatusOverridden` (sau commit); `order_events.type = status_overridden`; audit `order.status_overridden`. Fulfillment nghe: tới hoàn tất → huỷ vận đơn chưa rời kho + chốt hàng đang giữ (giao ngoài hệ thống, tồn khớp).
- Hộp nhập dùng chung (`promptFields`): trường có `options` thành ô chọn; thao tác hàng loạt (`ExtensionAction`) nhận `fields` (hỏi giá trị rồi gửi kèm `ids`).

## 0.3.98 — 2026-11-06

Thẻ "Trạng thái đơn" trên trang đơn Admin — nhân viên đổi trạng thái tại một chỗ; plugin thanh toán/vận chuyển cắm thao tác vào.

### Thêm
- Contract `Ordering\Contracts\OrderStatusAction` (tag `vani.ordering.status_actions`) + `Data\OrderStatusActionView`: plugin khai báo nút đổi trạng thái theo từng đơn (`describe` → null khi không áp dụng), Core chạy qua `POST {admin}/orders/orders/{order}/status-actions/{code}` (còn áp dụng → quyền `permission` → validate `fields` → `perform` → audit `order.status_action`).
- Hoàn tất đơn sớm (`POST {admin}/orders/orders/{order}/complete`, lý do bắt buộc): đơn đang xử lý, đã giao hết, không có đổi/trả đang mở (`OrderPolicy::staffCanComplete`). Lỗi `order.cannot_complete`.
- Thẻ hiển thị 4 chiều trạng thái (đơn, thanh toán, giao hàng, đổi/trả — nhãn `ordering::labels.fulfillment_status`, `return_status` mới) và nút Xác nhận / Hoàn tất / Huỷ đơn + nút của plugin; nút `primary` thành "bước tiếp theo" ở đầu trang.

### Đổi
- Nút "Xác nhận đơn", "Huỷ đơn" chuyển từ đầu trang vào thẻ "Trạng thái đơn" (Xác nhận vẫn là nút "bước tiếp theo" ở đầu trang).

## 0.3.97 — 2026-11-06

Vận hành đơn (đợt 2): trao đổi file với hãng vận chuyển, in phiếu giao, thẻ Khách.

### Thêm
- Xuất file cho hãng: thao tác hàng loạt "Xuất file cho hãng" (`GET {admin}/fulfillment/shipments/export?ids[]=`) → .xlsx mỗi vận đơn chưa rời kho một dòng (người nhận, SĐT giữ số 0 đầu, địa chỉ, sản phẩm, số lượng, khối lượng, giá trị hàng, COD, Hãng, Mã vận đơn). Khối lượng biến thể chưa khai báo: `vanishop.fulfillment.default_item_weight_gram` (`VANI_FULFILLMENT_DEFAULT_ITEM_GRAM`, 300).
- Nhập mã vận đơn từ file (`POST {admin}/fulfillment/shipments/import-tracking`, .xlsx/.csv): cột số đơn của shop tìm theo dữ liệu (file hãng xuất), cột mã vận đơn/hãng theo tiêu đề; ô hãng trống → hãng nhập ở form; dòng đã có mã bỏ qua; lỗi báo theo dòng (`import.N`).
- `Shared\Support\SimpleXlsx` — đọc/ghi .xlsx tối giản (một sheet, chữ/số) không thêm phụ thuộc.
- In phiếu giao/đóng gói (`GET {admin}/orders/orders/print?ids[]=`, A5 mỗi đơn một trang; thao tác hàng loạt + nút trên trang đơn).
- Thẻ Khách trên trang đơn: lịch sử nhận hàng theo SĐT (`OrderReader::deliveryHistory`) — Khách mới / Khách quen / Từng hoàn hàng.
- Slot `vani.admin.order.list_tools` (công cụ tải file ở đầu danh sách đơn). Thao tác hàng loạt (`ExtensionAction`) nhận `method: 'get'` + `target: '_blank'` để mở trang/tải file.

## 0.3.96 — 2026-11-06

Vận hành đơn (đợt 1): tự xác nhận COD theo cấu hình, hàng đợi việc ở danh sách đơn, thao tác ngay trên trang đơn.

### Thêm
- Giao hàng nhập tay rút gọn: nhân viên ghi Đã bàn giao hãng (`picked_up`, nhãn mới) / Giao không thành công / Đã giao / Đang hoàn / Đã hoàn về; "Đang vận chuyển", "Đang giao" chỉ đến từ hãng có kết nối (route vẫn nhận).
- `OrderReader::deliveryHistory(string $phone, ?int $exceptOrderId = null)` → `{orders, delivered, returned_to_sender, cancelled}` theo số điện thoại (cả khách vãng lai) — đánh giá rủi ro đơn COD.
- Slot `vani.admin.order.list_columns` (cột thêm ở danh sách đơn Admin, `values` id đơn ⇒ chữ), `vani.admin.order.bulk_actions` (thao tác hàng loạt POST `{ids}`) và `vani.admin.order.payment_methods` (nhãn cách thanh toán — Payment cung cấp) — cho module Core; plugin vẫn dùng AdminScreen. Fulfillment góp cột "Vận đơn" và thao tác "Tạo vận đơn", "Đã bàn giao cho hãng" (`POST {admin}/fulfillment/shipments/bulk-create`, `bulk-hand-over`).
- Danh sách đơn Admin theo hàng đợi việc: giai đoạn vận hành `Ordering\Domain\OrderStage` (tính từ 4 chiều trạng thái — Cần xác nhận, Chờ thanh toán, Chờ giao hãng, Đang giao, Hoàn về, Đổi/trả, Đã giao, Hoàn tất, Đã huỷ) với số đếm; lọc ngày đặt, nguồn, cách thanh toán; cột sản phẩm, tuổi đơn, tỉnh; "Xác nhận đơn" hàng loạt (`POST {admin}/orders/orders/bulk-confirm`).
- Trang đơn Admin là chỗ làm việc: panel `vani.admin.order.sidebar` nhận thêm `actions` (nút POST ngay trên trang đơn; `fields` → hộp nhập; `primary` → nút "bước tiếp theo" ở đầu trang); thanh tiến trình theo giai đoạn; slot `vani.admin.order.timeline` gộp sự kiện vào lịch sử đơn (sự kiện đơn có thêm `ts`). Fulfillment: Tạo vận đơn → Nhập mã + hãng → Đã bàn giao cho hãng → Đã giao / Giao không thành công / Đang hoàn về → Đã nhận hàng hoàn về. Payment: Xác nhận đã nhận tiền, Hoàn tiền, Đã chuyển trả; nhãn trạng thái thay cho mã thô.
: cấu hình `auto_confirm_mode` = `all` | `trusted` (SĐT đã có ≥ `trusted_min_delivered` đơn giao thành công, chưa từng bị hoàn về) | `off`; chưa chọn → theo công tắc cũ `auto_confirm` / `VANI_COD_AUTO_CONFIRM`. Lý do trong lịch sử đơn: `cod_auto_confirm`, `cod_auto_confirm_trusted`.

## 0.3.95 — 2026-11-06

Giỏ còn hàng mà khách đã bỏ chọn hết vẫn thanh toán được (giỏ mini và trang giỏ).

### Thêm
- Giỏ (Storefront API + native): `payable_line_ids` — id các dòng mua được (không hết hàng/ngừng bán).
- `POST gio-hang/chon` nhận `checkout=1`: chọn các dòng gửi lên rồi chuyển sang checkout (JSON: `redirect`).
- Partial theme `partials.checkout-button`: giỏ sẵn sàng → link checkout; chưa sẵn sàng nhưng còn dòng mua được → form chọn các dòng đó + sang checkout; không còn dòng nào → khoá.

### Đổi
- Giỏ mini và trang giỏ `vani-base` dùng `partials.checkout-button` (trước: nút khoá khi chưa chọn dòng nào).

## 0.3.94 — 2026-11-06

Trang giỏ đầy đủ dùng cùng API giỏ (không tải lại trang).

### Thêm
- JSON giỏ nhận header `X-Vani-Cart-View: page` → thêm `page_html` (partial theme `partials.cart-page` — thân trang giỏ, `pages.cart` include lại). `window.vani` tự gửi header này khi trang có `[data-vani-cart-page]`.
- Theme: thuộc tính `[data-no-js]` — phần tử chỉ hiện khi tắt JS (nút "Cập nhật" số lượng, "Áp dụng lựa chọn").

### Đổi
- Trang giỏ `vani-base`: sửa số lượng (0 = xoá), xoá dòng, chọn dòng / chọn tất cả, áp/bỏ mã giảm giá gọi `window.vani.cart` và thay vùng `[data-vani-cart-page]`; lỗi hiện tại chỗ. Không JS: form POST như cũ.

## 0.3.93 — 2026-11-06

Giỏ mini offcanvas + API cho frontend nạp dữ liệu không tải lại trang (native storefront, theme `vani-base`).

### Thêm
- Route web của storefront trả JSON khi request gửi `Accept: application/json` (cùng phiên, validation, throttle; form không JS giữ nguyên): giỏ `GET/POST gio-hang`, `gio-hang/{line}`, `gio-hang/{line}/xoa`, `gio-hang/chon` → `{cart, count, voucher, html, change, message, redirect}`; đăng nhập `tai-khoan/dang-nhap/otp` → `{message, phone}`, `tai-khoan/dang-nhap` → `{signed_in, redirect, csrf_token}`; `tai-khoan/dang-xuat`.
- Mã giảm giá ở giỏ: `POST gio-hang/ma-giam-gia` (`cart.voucher`, en `cart/voucher`; `code` rỗng = bỏ mã) — giữ trong phiên (`Storefront\Application\CartVoucher`), checkout điền sẵn, đặt hàng xong thì quên. Trang giỏ có ô nhập mã (chạy cả khi tắt JS).
- `window.vani` trong `app.js` của theme: `request`, `cart.{get,add,update,remove,select,applyVoucher,removeVoucher,open,close}`, `auth.{requestOtp,verify,logout}`, `search.{suggest,listing}`, `urls`. Sự kiện `vani:cart` (trạng thái giỏ), `vani:content-updated` (theme vừa thay HTML — plugin tô lại nút/nhãn).
- Partial theme `partials.mini-cart` (nội dung giỏ mini, server render trong JSON `html`) và `partials.cart-voucher`; layout có `<dialog data-vani-offcanvas-cart>` và `script[data-vani-urls]`.
- Lọc/phân trang danh sách (`[data-vani-listing]`) và đăng nhập OTP không tải lại trang; thêm vào giỏ ở trang sản phẩm mở giỏ mini.

### Đổi
- Header: "Giỏ hàng" mở giỏ mini offcanvas (không JS: link trang giỏ); bỏ dropdown giỏ mini cũ (`data-vani-mini-cart*`).
- Lỗi nghiệp vụ (`BusinessRuleViolation`) trong request web gửi JSON: trả `{message, errors: {business}, code}` với mã trạng thái của lỗi thay vì trang lỗi 500.
- `vani.wishlist` 1.0.1: tô lại nút tim khi nhận `vani:content-updated`.

## 0.3.92 — 2026-11-06

Theme `vani-base`: carousel (Swiper 14) và xem ảnh phóng to (PhotoSwipe 5), cài qua npm (MIT), Vite tách chunk, tải theo nhu cầu (import động).

### Thêm
- `window.vaniLoadVendor('swiper' | 'photoswipe')` → Promise lớp `Swiper` / `PhotoSwipeLightbox` (tải CSS + JS một lần) cho theme con và plugin.
- Carousel khai báo bằng HTML: `.vani-carousel.swiper > .swiper-wrapper > .swiper-slide`, tuỳ chọn Swiper ở `data-carousel` (JSON), nút `[data-carousel-prev]`/`[data-carousel-next]`, chấm `[data-carousel-pagination]`. Không JS: dải cuộn ngang scroll-snap.
- Ảnh phóng to: vùng `[data-vani-lightbox]` chứa `<a href="ảnh lớn" data-pswp-width data-pswp-height>`. Không JS: liên kết mở ảnh.

### Đổi
- Trang sản phẩm: gallery thành carousel (vuốt, mũi tên, ảnh nhỏ cuộn ngang đồng bộ) + bấm ảnh phóng to (bản lớn nhất trong `srcset`); chọn màu → tới ảnh đầu của màu. Ảnh sau ảnh đầu tải lười. `data-gallery-thumb` có `data-index`; ảnh không phải ảnh đầu mang `data-gallery-image`.

## 0.3.91 — 2026-11-06

Thư viện ảnh chứa cả video (MP4/WebM).

### Thêm
- Tải lên MP4/WebM vào Thư viện ảnh (giới hạn riêng `media.max_video_kb` / `VANI_MEDIA_MAX_VIDEO_KB`, mặc định 100 MB); kích thước khung MP4 đọc từ hộp `tkhd` (WebM để trống). Video không có bản thu nhỏ: `vani_image()`/`MediaUrls` trả file gốc, `srcset` rỗng.
- `FieldDefinition::video()` (kiểu trường `video`, giá trị là id) — ô chọn video trong form khối/phần form plugin; khối trang chủ khai báo video đang dùng (`storefront.home`) như ảnh.
- Helper `vani_media(?int $id): string` — URL file gốc (ảnh hoặc video).
- `MediaData::$mimeType` + `isVideo()`; duyệt Thư viện (`/admin/media/browse`) nhận `kind=image|video`, trả `kind` từng mục và `limits`.
- Admin: `MediaManager`/`MediaPicker`/`MediaField` có prop `kind` (`MediaPicker`/`MediaField` mặc định `image` — ô chọn ảnh hiện có không thấy video).

### Đổi
- `vani.storefront-extras` 1.2.0 (cần Core `^0.3.91`): Video hero chọn video từ Thư viện ảnh (`video`, `video_mobile`) thay cho ô nhập `video_url`/`video_mobile_url`; cấu hình cũ có URL https .mp4/.webm vẫn hiện.

## 0.3.90 — 2026-11-06

Ảnh chỉ chọn từ Thư viện ảnh (không còn nhập URL ảnh).

### Đổi
- Khối trang chủ: `hero` bỏ `image_url` (chỉ `image`); `vani.storefront-extras` 1.1.0 bỏ `slide_N_image`, `cell_N_image`, `poster_url` → `slide_N_media`, `cell_N_media`, `poster` (ảnh Thư viện ảnh, srcset). Cấu hình cũ còn URL vẫn hiển thị (chỉ đọc).
- Migration `home_block_images_to_media`: URL trỏ về ảnh của Thư viện ảnh (bản thu nhỏ `/cache/media/…/{checksum}-…` hoặc file gốc) → id ảnh + khai báo đang dùng (`storefront.home`); URL ngoài giữ nguyên.
- Thương hiệu: cột `brands.logo_media_id`, Admin → Catalog → Thương hiệu có ô chọn logo (khai báo đang dùng `brand`/`logo`); `logo_url` của Storefront/Catalog dùng bản thu nhỏ 400px, `logo_path` cũ chỉ còn đọc.

## 0.3.89 — 2026-11-06

Chỉ thêm.

### Thêm
- Extension point `Inventory\Contracts\StockPageLink` (tag `vani.inventory.stock_links`): nút trên trang Admin → Tồn kho do plugin đóng góp (`label`, `url` — null = ẩn, `managesLocations` — true thì Core ẩn form "Kho chính"). `vani.warehouse` đóng góp "Chuyển kho", "Kho & cửa hàng" → **tắt plugin là nút biến mất** (trước: Core tra route của plugin, route vẫn đăng ký khi plugin tắt nên nút còn hiện). Inertia prop `links` thay `urls.transfers`.

## 0.3.88 — 2026-11-06

Tách vận hành kho thành plugin `vani.warehouse` (ADR-042); Inventory (engine, dữ liệu tồn) ở lại Core.

### Thêm
- `Inventory\Contracts\LocationRegistry` (`all`, `find`, `save` — audit, optimistic lock, mã trùng → ValidationException) + `Contracts\Data\LocationData` (`TYPES`, `managesOnHand()`).
- `Inventory\Contracts\StockMovements` (`transferOut`, `transferIn`; `StockMovementRejected`): biến động chuyển kho qua sổ biến động, trong transaction của bên gọi.
- `Inventory\Contracts\InventoryReconciliation::reconcileExternal()`.
- Plugin `vani.warehouse` 1.0.0: Kho & cửa hàng, chuyển kho (`plg_warehouse_transfers(_lines)` — đổi tên từ `stock_transfers` khi nâng cấp), `vani:warehouse:reconcile`; chặn tắt khi còn phiếu đang đi đường.
- Admin Core: "Kho chính" (sửa tên/địa chỉ/tỉnh) trên trang Tồn kho khi chưa bật `vani.warehouse`.

### Đổi / gỡ
- Gỡ khỏi Core: màn hình Kho & cửa hàng (`admin.inventory.locations.*`), chuyển kho (`admin.inventory.transfers.*`, quyền `inventory.transfer`), lệnh `vani:inventory:reconcile`, bảng/migration `stock_transfers(_lines)`. Thay bằng `admin.plugins.vani-warehouse.*`, quyền `warehouse.locations.manage` / `warehouse.transfer`, `vani:warehouse:reconcile`.
- Event `Inventory\Events\StockTransfer*` giữ nguyên (do `vani.warehouse` phát).
- `LocationService::save` kiểm mã kho trùng (ValidationException `code`).
- Test kiến trúc: plugin không nhắc tên bảng tồn kho của Core / `Modules\Inventory\Persistence`.

## 0.3.87 — 2026-11-06

Ảnh thu nhỏ cấu hình trong Admin, kích thước riêng của plugin, ô chọn ảnh dùng chung.

### Thêm
- Extension point `Catalog\Contracts\ImageSizeProvider` (tag `vani.catalog.image_sizes`) + `Contracts\Data\ImageSize` (`name`, `label`, `width`, `height?`, `crop`): plugin khai báo kích thước ảnh có tên; nhân viên ghi đè rộng/cao/cắt khung ở Admin → Thư viện ảnh → **Kích thước ảnh**. Tham chiếu: `vani.lookbook` 1.1.0 (`lookbook.cover` 600×900 cắt khung). Contract test `Catalog\Testing\ImageSizeProviderContract`.
- Admin → Thư viện ảnh → Kích thước ảnh: bề rộng chung (thu nhỏ giữ tỉ lệ), **tỉ lệ ảnh sản phẩm** (mặc định **2:3**, cắt giữa ảnh; `original` = giữ tỉ lệ gốc), chất lượng, nút xoá cache ảnh. Lưu ở Settings `core`/`media.*` (mặc định theo `vanishop.media.cache.*`).
- `MediaUrls::url/urls/image` nhận `int|string|null $size` (số = bề rộng, chuỗi = tên kích thước) + `?string $group` (`product`); thêm `MediaUrls::srcset()`, `MediaUrls::productRatio()`. Helper `vani_image($id, int|string $size, ?string $group)`, `vani_srcset($id, ?$group)`, `vani_product_ratio()`.
- `FieldDefinition::media()` / kiểu `media` (id ảnh) cho phần form plugin, khối trang chủ; `SettingDefinition` kiểu `media`. Admin render bằng component dùng chung `@admin/Components/Media/MediaField.vue` (v-model id hoặc danh sách id; xem trước qua `GET /{admin}/media/items?ids[]=`). Khối trang chủ có trường `media` được khai báo "đang dùng" (`storefront.home`).
- Khối `hero` có trường `image` (ảnh Thư viện ảnh, srcset); `vani.storefront-extras` trình chiếu/lưới banner có `slide_N_media`/`cell_N_media` (giữ trường URL cũ).

### Đổi
- URL ảnh thu nhỏ `…/{checksum}-{kích thước}.{ext}` với kích thước `w400` | `w400h600` | `w400h600c` (trước chỉ `-w{width}`); bản `-w{width}` cũ vẫn hợp lệ khi bề rộng còn trong cấu hình. `ImageCache::render/path` nhận khoá kích thước (chuỗi).
- Ảnh sản phẩm (thẻ, PDP, giỏ, màu) dùng nhóm `product`: cắt theo tỉ lệ cấu hình, `srcset` không kèm ảnh gốc khác tỉ lệ. PDP: ảnh chính có `srcset` + `fetchpriority="high"`, ảnh nhỏ dùng bản 160px (`images[].srcset`, `images[].thumb_url` trong Storefront API). Theme dựng khung theo `vani_product_ratio()` thay `aspect-[3/4]` cố định.
- `ImageCache`: đọc ảnh gốc thẳng từ file khi disk local; JPEG progressive; `--warm` chỉ tạo bản cắt sản phẩm cho ảnh trong gallery sản phẩm. `MediaUrls` scoped theo request (ghi nhớ ảnh đã tra). Cấu hình "Ảnh thay thế" thành ô chọn ảnh.

## 0.3.86 — 2026-11-05

Không đổi API plugin.

### Gỡ
- Plugin `vani.demo-catalog` và lệnh `vani:demo:catalog`: dữ liệu demo chuyển vào `database/seeders/DemoSeeder` (`php artisan db:seed --class=DemoSeeder`, từ chối chạy ở production). Bộ ảnh ở `database/seeders/Demo/photos`; cấu hình `vanishop.demo.images|image_max` (`VANI_DEMO_IMAGES`, `VANI_DEMO_IMAGE_MAX`). Contract `CatalogImporter`/`PriceImporter`/`StockImporter` giữ nguyên.

## 0.3.85 — 2026-11-05

Chỉ thêm. Phục vụ `vani.scarcity-signals` (M2).

### Thêm
- `Ordering\Contracts\OrderStatistics::confirmedQuantitiesByStyle(from, to, ?styleIds)`: số lượng đã bán chắc chắn theo style — như `topStyles` nhưng chỉ đơn đã thu tiền (`authorized`, `paid`, `partially_refunded`, `cod_collected`) hoặc đã rời kho (`partially_shipped`, `shipped`, `delivered`); COD vừa đặt, đơn chưa trả, hoàn tiền toàn bộ không tính.
- `StorefrontEnricher` resource `product_card`: phần tử có thêm `variant_ids` (chỉ cho enricher; bỏ trước khi trả ra).
- Plugin `vani.scarcity-signals` 1.0.0.

## 0.3.84 — 2026-11-05

Không đổi API plugin. Admin → Tồn kho sửa linh hoạt hơn.

### Đổi
- Lưới tồn gom theo sản phẩm, phân trang theo sản phẩm (20/trang, mọi SKU của một sản phẩm cùng trang); lọc thêm màu, size.
- Chọn nhiều: từng SKU, cả sản phẩm, cả trang, hoặc mọi SKU khớp bộ lọc → sửa hàng loạt tại một kho (`POST /{admin}/inventory/stock/bulk`): đặt số tồn, cộng/trừ (bắt buộc lý do), hết hàng (đặt 0), tồn an toàn. Mỗi SKU một biến động; SKU không áp được (trừ quá tồn) bỏ qua và báo số lượng; audit `inventory.stock.bulk` cho cả lô.
- `StockAdjustmentService::setSafetyStock` trả `bool` (có thay đổi). Enter/Shift+Enter trong ô tồn chuyển xuống/lên dòng.

## 0.3.83 — 2026-11-05

Chỉ thêm. Tồn kho dễ cấu hình (đợt 1).

### Thêm
- `Catalog\Contracts\VariantDirectory::search(text, limit = 2000)`: tìm variant theo SKU, mã hoặc tên sản phẩm (chuỗi rỗng = mọi variant), sắp theo tên → màu → size.
- Lệnh `vani:inventory:ensure-location` (cửa hàng chưa có kho → tạo `KHO-CHINH` "Kho chính": giao online, nhận hàng trả, VaniShop quản lý tồn); `vani:install` gọi lệnh này.
- Admin → Tồn kho: lưới mọi sản phẩm (tìm, lọc hết hàng / sắp hết, phân trang), sửa số tồn trong ô và lưu nhiều ô một lần (như kiểm kê, ô có tồn đổi sau khi mở trang không bị ghi đè), nhập CSV có xem trước, xuất CSV; form sản phẩm có nút "Tồn kho".

### Đổi
- Inertia props của `Inventory::Stock/Index` đổi dạng (`urls`, `query`, `pagination`, `warnings`, `variants[].sellable`) — trang Core, không phải API plugin.

## 0.3.82 — 2026-11-05

Không đổi API. Cửa hàng chỉ tiếng Việt (Owner 2026-11-05), giữ cơ chế đa ngôn ngữ.

### Đổi
- `vanishop.locale.supported` đọc từ `VANI_LOCALES` (phân tách dấu phẩy), **mặc định chỉ `vi`** (trước: luôn `vi`, `en`). Khi chỉ một ngôn ngữ: không route `/<mã>`, không hreflang/nút chuyển ngôn ngữ, sitemap không có bản khác, Admin chỉ một tab bản dịch, cấu hình theo ngôn ngữ của plugin (`key.<mã>`) chỉ có khoá `vi`.
- Plugin không giả định có `en`: lặp `StoreLocale::supported()` như trước. Test (phpunit) chạy `VANI_LOCALES=vi,en` để giữ cơ chế đa ngôn ngữ được kiểm.

## 0.3.81 — 2026-11-05

Chỉ thêm. Ảnh đính kèm yêu cầu đổi trả ([reference-parity §3.13-3](../20-roadmap/reference-parity-refactor.md)).

### Thêm
- `Returns::attachPhotos(orderId, returnPublicId, photos, source)`, `Returns::MAX_ATTACHMENTS` (5); `ReturnView::$attachments` (`[{id, url}]` — link có chữ ký 30 phút, route `returns.attachment`); `ReturnRejected::attachmentsInvalid()` (`return.attachments_invalid`).
- Bảng `return_attachments`; cấu hình `vanishop.returns.attachments_disk` (`VANI_RETURN_ATTACHMENTS_DISK`, mặc định `local` — không public).
- Storefront API `POST /orders/{id}/returns` và form web nhận `photos[]` (JPEG/PNG/WebP ≤ 5 MB, tối đa 5); đơn có `returns[].attachments`.

## 0.3.80 — 2026-11-05

Không đổi public API. Vận hành ([reference-parity §3.20](../20-roadmap/reference-parity-refactor.md)).

### Thêm
- Sitemap index: quá 45 000 URL → `/sitemap.xml` là `sitemapindex`, file con `/sitemap/{n}.xml` (route `storefront.sitemap.page`); `SitemapProvider::urls($limit)` nhận trần lớn hơn (tới 900 000). Đoạn `sitemap` thêm vào danh sách giữ chỗ (plugin không dùng được làm prefix).
- Lệnh `vani:docs:schema [--check]` → `docs/07-database/schema.generated.md`.

## 0.3.79 — 2026-11-05

Chỉ thêm. Lượt xem sản phẩm + đã xem gần đây ([reference-parity §3.5-2](../20-roadmap/reference-parity-refactor.md)).

### Thêm
- Event `Catalog\Events\ProductViewed(styleId, ?customerId)` (đã lọc trùng cùng phiên 30 phút).
- Contract `Catalog\Contracts\ProductViewStatistics` (`record`, `counts(styleIds, days)`, `top(days, limit)`); bảng `style_view_stats` (gom theo giờ, không lưu người xem).
- Beacon `POST /_vani/xem` (`style_id`, miễn CSRF, 204). Theme `vani-base`: PDP gửi beacon, lưu tối đa 12 mẫu trong `localStorage` (`vani.recently_viewed` — plugin đọc được) và hiện "Đã xem gần đây" từ `GET /products?ids=`.

## 0.3.78 — 2026-11-05

Chỉ thêm. Khách xác nhận đã nhận hàng ([reference-parity §3.11-1](../20-roadmap/reference-parity-refactor.md)).

### Thêm
- `CustomerOrders::confirmReceived(publicId, token, waiveReturns)`, `confirmReceivedForCustomer(customerId, publicId, waiveReturns)`; `OrderDetail::$receipt` (`confirmed_at`, `returns_waived`, `can_confirm`); `OrderActionRejected::cannotConfirmReceipt()` (`order.cannot_confirm_receipt` 409).
- `orders.meta.returns_waived` → `Returns` từ chối yêu cầu mới (`return.not_eligible`, reason `waived`), `returnable()` rỗng.
- Storefront API `POST /orders/{id}/received` (`waive_returns`); web `storefront.order.received`; đơn có `receipt`. Partial `theme::partials.order-receipt` của `vani-base`.

## 0.3.77 — 2026-11-05

Không đổi public API. Trang thương hiệu theo chữ cái ([reference-parity §3.5-3](../20-roadmap/reference-parity-refactor.md)).

### Thêm
- View `theme::pages.brands` nhận thêm `$brandGroups` (chữ cái => thương hiệu; bỏ dấu khi nhóm, Đ nhóm riêng, ký tự khác vào `#`). `$brands` giữ nguyên.

## 0.3.76 — 2026-11-05

Chỉ thêm. Phục vụ plugin `vani.wishlist` 1.0.0 ([đặc tả](../05-plugin/specs/wishlist.md) §5).

### Thêm
- `PluginServiceProvider::accountRoutes($file, ['vi' => 'yeu-thich', 'en' => 'wishlist'])`: trang plugin dưới trang tài khoản (`/tai-khoan/…`, `/en/account/…`), bắt buộc đăng nhập, tên route `storefront.p.{slug}.account.…`; đoạn đường dẫn không trùng trang tài khoản Core/plugin khác (`StorefrontPrefixes::claimAccount`).
- Dữ liệu phiên (`/_vani/phien`, `script[data-vani-session-state]`) có `csrf_token`; `vani-base` đặt `window.vaniSession` và phát `document` event `vani:session` (detail = dữ liệu phiên) — JS của plugin gửi POST từ trang cache CDN bằng header `X-CSRF-TOKEN`.

### Đổi
- Menu tài khoản của `vani-base`: link Core theo ngôn ngữ trang (`vani_route`), nhãn mục plugin qua `__()` (plugin dịch bằng JSON).

## 0.3.75 — 2026-11-05

Chỉ thêm. Thanh toán lại ([reference-parity §3.11-2](../20-roadmap/reference-parity-refactor.md)).

### Thêm
- `Payments::latestForOrder(orderId)`, `Payments::retry(orderId, ?gatewayCode)`; `PaymentView::$retryable`; `PaymentRejected::expired()` (`payment.expired` 409).
- Khoản đang chờ cùng cổng → khởi tạo lại (link/QR mới); khoản lỗi hoặc đổi cổng online → khoản cũ `cancelled`, khoản mới cùng số tiền và cùng `expires_at` (`meta.retry_of`). Plugin cổng: mỗi lượt có `publicId` riêng — dùng làm mã giao dịch như hiện nay là đủ.
- Storefront API `POST /orders/{id}/payment/retry` (`gateway` tuỳ chọn); web `storefront.order.pay`; thanh toán của trang đơn (`payment.retryable`) lấy theo khoản mới nhất, không cần phiên lúc đặt.

## 0.3.74 — 2026-11-05

Danh mục lý do đổi trả + khách báo đã gửi hàng về ([reference-parity §3.13-1/2](../20-roadmap/reference-parity-refactor.md)).

### Thêm
- `Returns::reasons(locale)`, `reasonLabel(code, locale)`, `markShipped(orderId, returnPublicId, trackingNumber, source)`; `ReturnView::$returnTrackingNumber`; `ReturnRejected::reasonInvalid()`.
- Bảng `return_reasons` (dữ liệu ban đầu từ `vanishop.returns.reasons`), cột `return_requests.return_tracking_number`.
- Storefront API `POST /orders/{id}/returns/{returnId}/shipped` (`tracking_number` tuỳ chọn); đơn có `returns[].reason` (tên theo ngôn ngữ) và `returns[].return_tracking_number`. Route web `storefront.order.returns.shipped`.

### Đổi
- Lý do hợp lệ = lý do đang bật trong danh mục (không còn đọc `vanishop.returns.reasons` — chỉ là dữ liệu ban đầu). `Returns::request` từ chối lý do không có/đã tắt (`return.reason_invalid`) cho mọi kênh.

## 0.3.73 — 2026-11-05

Chỉ thêm. Khối lượng + số rút gọn ([reference-parity §3.1-1/2, §3.10-2](../20-roadmap/reference-parity-refactor.md)).

### Thêm
- `Shared\Domain\Weight\Weight` (gram nguyên; `of(1.2, 'kg')`, `add`, `multiply`, `kilograms`).
- `SellableVariant::$weightGram`, `TotalsLine::$weightGram` (+ `weight()`), `TotalsContext::weight()` / `missingWeight()` — `ShippingRateProvider` tính phí theo khối lượng các dòng của đơn (chỉ dòng giỏ đã chọn). `Checkout\Testing\Samples` có khối lượng (1.100 g).
- Định dạng `compact`, `money_compact` cho `Metric`/`Series`/`Column` (dashboard: 1,3 N · 2,4 Tr ₫); `MoneyFormatter::compact()`/`compactMoney()`; Admin `app.money.compactUnits`.
- `vani.shipping-flat-rate` 1.2.0: `included_grams` + `fee_per_extra_kg`. `vani.reports` 1.1.0 (cần Core ^0.3.73): số rút gọn cho doanh thu.

## 0.3.72 — 2026-11-05

Không đổi public API. Hồ sơ nhân viên ([reference-parity §3.3-3/5](../20-roadmap/reference-parity-refactor.md)).

### Thêm
- Cột `staff_users.locale`; Admin chạy theo ngôn ngữ của nhân viên (`App::getLocale()` trong request Admin) — plugin dịch thông báo Admin bằng `__()` như thường.
- Inertia shared `urls.profile`; lệnh `vani:staff:reset-password`.

## 0.3.71 — 2026-11-04

Chỉ thêm. Bộ lọc cấu hình được + gợi ý tìm kiếm ([reference-parity §3.5-1/4](../20-roadmap/reference-parity-refactor.md)).

### Thêm
- Kết quả `CatalogReader::searchProducts` (và hook `vani.service.catalog.search`) có `filters` = list<{key, param, label, display, options[{value, label, count, swatch?}]}> theo cấu hình Admin → Catalog → Bộ lọc (`core`/`catalog.facets`); `facets` giữ nguyên. Storefront API `GET /products` trả `meta.filters`.
- `CatalogReader::suggestions()`, hook `vani.service.catalog.suggestions`, Storefront API `GET /search/suggestions?q=&limit=`.
- `ListingUrl` nhận khoá lồng dạng chấm (`attr.material`). Theme `vani-base` render `filters` (cả thuộc tính, ô màu) và dropdown gợi ý (`form[data-vani-suggest]`).

## 0.3.70 — 2026-11-04

Chỉ thêm. Cấu hình storefront còn lại ([reference-parity §3.2](../20-roadmap/reference-parity-refactor.md)).

### Thêm
- Khoá `core`: `storefront.per_page` (mặc định `ProductListRequest` khi không truyền `per_page`), `storefront.popular_searches`, `storefront.placeholder_image`.
- Thẻ sản phẩm (`ProductCards`, Storefront API) thiếu ảnh có `image_url` = ảnh thay thế khi đã cấu hình.
- Layout và trang tìm kiếm theme nhận `$popularSearches` (list<string>).

## 0.3.69 — 2026-11-04

Chỉ thêm. Helper ADR-037 + tài nguyên tĩnh plugin (reference-parity §3.4-1/2).

### Thêm
- Helper `vani_setting`, `vani_money`, `vani_locale`, `vani_customer`, `vani_staff`, `vani_admin_url`, `vani_image`, `vani_asset`, `vani_plugin` (`modules/Extension/helpers.php`); chữ ký mọi helper giờ nằm trong `public-api.snapshot`.
- `Catalog\Contracts\MediaUrls` (`url`, `urls`, `image` — ảnh thay thế từ `core`/`storefront.placeholder_image`).
- `Extension\Contracts\PluginDirectory` (`find`, `asset`), `Extension\Contracts\Data\PluginInfo`.
- Manifest `assets`: thư mục tài nguyên tĩnh publish sang `public/plugins/<id>/` (tên có hash) khi bật/nâng plugin và `vani:plugin:cache`; gỡ plugin thì xoá. Cấu hình `vanishop.plugins.assets.path|url` (`VANI_PLUGINS_ASSETS_PATH`, `VANI_PLUGINS_ASSETS_URL`).
- Arch test: class trong `modules/` không gọi helper ADR-037.

## 0.3.68 — 2026-11-04

Cache CDN cho trang công khai thành tuỳ chọn nâng cao ([storefront §5](../14-storefront/storefront.md)).

### Đổi
- `VANI_PAGE_CACHE` mặc định **false**: trang công khai chạy có phiên (khách, `$errors`, flash có sẵn khi render), `Cache-Control: private, no-store`. Bật lại khi deploy có CDN. Trước đây tắt chỉ đổi header, trang vẫn không phiên.
- Thuộc tính request `vani.page_cache` (`PublicPageCache::ATTRIBUTE`) chỉ có khi chế độ CDN bật; thêm `vani.public_page` (`PublicPageCache::PUBLIC_PAGE`) cho mọi trang công khai — SEO (canonical, hreflang) dùng cờ này.
- Miễn CSRF cho `POST` giỏ ở mọi ngôn ngữ (trước chỉ `/gio-hang`; `/en/cart` từ trang cache bị 419).
- `/_vani/phien`: nhãn tài khoản theo `?locale=`.

### Thêm
- Layout theme nhận `$customerState` (null ở chế độ CDN): `{signed_in, account{label, url}, cart{count, subtotal, lines}, prices_url}`; `vani-base` render sẵn số món và nhúng `script[data-vani-session-state]`, `app.js` dùng chung một hàm cho dữ liệu nhúng và `/_vani/phien`. Theme tự viết nên làm tương tự để chạy đúng khi không có CDN.

## 0.3.67 — 2026-11-04

Chỉ thêm. Ảnh responsive ([storefront §4d](../14-storefront/storefront.md)).

### Thêm
- Thẻ sản phẩm (`CatalogReader` danh sách/`productsByIds`, Storefront API `GET /products`) có `image_srcset` (string|null).
- `Storefront\View\ResponsiveImage::sizes()`; partial `theme::partials.image` của `vani-base`; `theme::partials.product-card` nhận `layout` = [vùng chứa px, cột desktop, cột mobile].

## 0.3.66 — 2026-11-04

Chỉ thêm. Breadcrumb ([storefront §4c](../14-storefront/storefront.md)).

### Thêm
- `CatalogReader::category()` trả thêm `breadcrumb` = list<{id, slug, name}> từ gốc tới chính danh mục (cả Storefront API `GET /categories/{slug}`).
- `Storefront\View\StructuredData::breadcrumbs()` (JSON-LD `BreadcrumbList`); partial `theme::partials.breadcrumb` của `vani-base` dùng ở PDP, danh mục, thương hiệu.

## 0.3.65 — 2026-11-04

Chỉ thêm. Đầu & chân trang theo schema theme ([storefront §4b](../14-storefront/storefront.md)).

### Thêm
- `theme.json` khai báo `settings` (nhóm → trường kiểu `FieldDefinition` + `url`/`email`, `translatable`); theme con không khai báo thì dùng của theme cha.
- Layout theme nhận `$themeSettings` (giá trị theo ngôn ngữ trang). `vani-base` khai báo `header` (thanh thông báo), `footer` (liên hệ, mạng xã hội, phương thức thanh toán, bản quyền).
- Admin → Giao diện → Đầu & chân trang.

## 0.3.64 — 2026-11-04

Chỉ thêm. Chọn dòng giỏ để thanh toán, mua ngay, giỏ mini ([reference-parity §3.8](../20-roadmap/reference-parity-refactor.md)).

### Thêm
- Cột `cart_lines.selected` (mặc định true). `CartLineView::$selected` (tham số cuối, mặc định true; dòng `unavailable` luôn false).
- `Carts::buyNow()`, `select()`, `deselect()`, `selectOnly()` (cùng khoá giỏ với các thao tác khác), `Carts::markOrdered()` (xoá dòng đã vào đơn; giỏ chỉ `converted` khi hết dòng).
- `CartView::selectedLines()`, `selectedSubtotal()`.
- Storefront API: `PUT /carts/{id}/selection` (`mode` = `only`|`select`|`deselect`, `line_ids[]`), `buy_now` khi thêm dòng; giỏ trả thêm `lines[].selected`, `selected_subtotal`, `selected_count`.
- `/_vani/phien`: `cart.subtotal`, `cart.lines[]` (3 dòng gần nhất) cho giỏ mini; `cart.count` giữ nguyên.

### Đổi
- `CartView::isCheckoutReady()` chỉ xét dòng đã chọn (cần ít nhất một); dòng chưa chọn hoặc ngừng bán không còn chặn thanh toán.
- Checkout (`quote`, `placeOrder`) chỉ lấy dòng đã chọn — khuyến mãi, phí giao, thuế tính trên phần đó; sau khi đặt gọi `markOrdered` thay `markConverted`. Thêm lại một dòng đã bỏ chọn thì dòng được chọn lại.

## 0.3.63 — 2026-11-04

Chuỗi giao diện storefront theo ngôn ngữ.

### Thêm
- Theme nạp `lang/<locale>.json` theo chuỗi theme (cha trước, con ghi đè); `vani-base` có `lang/en.json`. `vani.cms`, `vani.size-chart` có `Resources/lang/en.json`.
- Layout xuất khối `<script type="application/json" data-vani-i18n>` (id → chuỗi đã dịch) cho `app.js`.

### Đổi
- View `vani-base`, `vani.cms`, `vani.size-chart`, `vani.tracking-pixels`, `vani.hello-world` bọc chữ hiển thị bằng `__()`. Theme con override view nên làm tương tự (khoá = câu tiếng Việt) để có bản tiếng Anh.
- Nhãn trạng thái đơn cho khách (`customerStatus.label` trong chi tiết đơn, API `/me/orders`) theo ngôn ngữ của request (`ordering::labels.customer_status`).

## 0.3.62 — 2026-11-04

Chỉ thêm. Ngôn ngữ của đơn và khách (ADR-038 mục 8).

### Thêm
- Cột `orders.locale`, `customers.preferred_locale`.
- `OrderData::$locale`, `OrderPlaced::$locale`, `CustomerData::$preferredLocale` (tham số cuối, có mặc định); `CustomerData::toArray()` có `preferred_locale`.
- Thông báo đơn hàng chọn mẫu theo `orders.locale` (rơi về ngôn ngữ mặc định khi thiếu mẫu).

## 0.3.61 — 2026-11-04

SEO theo ngôn ngữ (ADR-038 mục 5–7).

### Thêm
- `Storefront\Contracts\PageSeo` (`alternates(urls, translated)`, `noindex()`); layout theme nhận `$seo` (`canonical`, `robots`, `hreflang`, `switcher`).
- Dữ liệu catalog có `locales` (ngôn ngữ đã dịch) trên sản phẩm và nút danh mục.

### Đổi
- `vani-base`: canonical/robots/hreflang do layout sinh — bỏ `@section('canonical')` ở trang sản phẩm, danh mục, thương hiệu và `<meta name="robots">` tĩnh ở trang giao dịch; thêm nút chuyển ngôn ngữ ở header. Theme riêng đặt `@section('canonical')` vẫn được ưu tiên.
- `sitemap.xml` liệt kê URL mọi bản đã dịch, kèm `xhtml:link`; khoá cache `vani:sitemap` (không theo locale).
- `vani.cms`: trang/bài khai báo một ngôn ngữ (bản `/en` `noindex`, canonical về bản tiếng Việt).

## 0.3.60 — 2026-11-04

Slug theo ngôn ngữ (ADR-038 mục 4).

### Thêm
- Cột `slug` (nullable, unique `(locale, slug)`) trên `style_translations`, `category_translations`, `collection_translations`.
- Chi tiết sản phẩm (`CatalogReader::productDetail`) và nút danh mục (`categoryTree`, `category`) có `slugs` (mã ngôn ngữ → slug).

### Đổi
- `slug` trong dữ liệu catalog (danh sách, chi tiết, giỏ, breadcrumb, `CatalogLookup::visible`) là slug **theo ngôn ngữ được yêu cầu** (Storefront API theo `X-Vani-Locale`); trước: luôn slug gốc. `productDetail`/`category` và bộ lọc `category`/`collection` nhận slug theo ngôn ngữ hoặc slug gốc.

## 0.3.59 — 2026-11-04

URL storefront theo ngôn ngữ, phần định tuyến (ADR-038 mục 1–3, 9).

### Thêm
- Route storefront tiếng Anh dưới `/en` (tên `storefront.en.*`); bảng đường dẫn `config/vanishop.php` → `locale.paths`.
- `Shared\Support\LocalizedUrls` (`route`, `name`, `has`, `baseName`); helper `vani_route()`, `vani_link()` (ADR-037).
- `PluginServiceProvider::storefrontPages()` nhận `prefix` dạng mảng theo ngôn ngữ.

### Đổi
- Trang web native lấy ngôn ngữ từ URL, không còn theo header `X-Vani-Locale`; trang có cache bỏ `X-Vani-Locale` khỏi `Vary`.
- `StorefrontPrefixes::claim($prefix, $pluginId, ?$locale)`, `all()` trả khoá `locale:prefix`; mã ngôn ngữ và đường dẫn tiếng Anh của Core bị giữ chỗ.
- Theme `vani-base`, `vani.cms`, `vani.vnpay` sinh URL bằng `vani_route()`. Theme riêng còn gọi `route('storefront.…')` sẽ ra URL tiếng Việt trên trang tiếng Anh — đổi sang `vani_route()`.

## 0.3.58 — 2026-11-04

Chỉ thêm. Link có kiểu và menu storefront (R1-6, [ADR-040](../19-adr/ADR-040-typed-links-menus.md)).

### Thêm
- `Storefront\Contracts\LinkType` (tag `vani.storefront.link_types`) — extension point loại link; contract test `Modules\Storefront\Testing\LinkTypeContract`.
- `Storefront\Contracts\LinkResolver`, `Data\LinkTarget`, `Data\ResolvedLink`.
- `Catalog\Contracts\CatalogLookup` (`visible(kind, ids, locale)`, `search(kind, query, locale, limit)`).
- `theme.json` khai báo `menus` (vị trí + độ sâu); layout theme nhận `$menus` (`MenuPresenter::items(location, locale)`); partial `theme::partials.menu`, `theme::partials.menu-list` của `vani-base`.
- Admin JS: trang `Storefront::Design/Menus`.
- `vani.cms` 1.3.0: loại link `page`, `article`.

## 0.3.57 — 2026-11-04

Chỉ thêm. Khách tự xoá tài khoản (R1-5, [reference-parity-refactor §3.15-1](../20-roadmap/reference-parity-refactor.md)).

### Thêm
- Hook validate `vani.customer.before_delete` (`customerId`): plugin báo việc còn dở chặn xoá tài khoản, trả `list<array{type, reference, label}>`.
- `CustomerAccounts::deletionBlockers()`, `requestDeletionOtp()`, `deleteAccount()`; lỗi `customer.deletion_blocked` (409, `details.blockers`).
- Storefront API `GET /me/delete`; native `/tai-khoan/xoa` (gửi OTP, xác nhận, đăng xuất).

### Đổi
- `POST /me/delete` từ chối khi còn đơn chưa hoàn tất/đổi trả đang mở (trước: luôn ẩn danh hoá); kiểm tra trước khi tiêu OTP.

## 0.3.56 — 2026-11-04

Hoàn thành bộ component Admin ([ADR-039](../19-adr/ADR-039-admin-ui-reka.md)). Chỉ thêm với plugin; trang Core đổi hình dạng một số prop.

### Thêm
- `@admin/Components`: `MultiSelect`, `DateInput` (`YYYY-MM-DD`, hoặc `withTime` → `YYYY-MM-DDTHH:mm`). `Select` nhận giá trị số/boolean/null (`emptyValue`) và phát sự kiện `change`. `DataTable`: slot `head-<key>`, `expanded`, `footer`; prop `rowKey`, `rowClass`, `extensionId`, `bare`; slot ô nhận `index`.
- `@admin/confirm`: `promptFields({ fields })` hỏi nhiều giá trị một lần; trường có `value` ban đầu.
- `@admin/datetime`: `useDateTime()`, `formatDateTime()` — định dạng theo `app.clock` (shared props mới: múi giờ + mẫu ngày giờ của cửa hàng). `@admin/options`: `labelOptions`, `codeOptions`, `recordOptions`.
- Lang `labels.php` cho từng module Core có màn hình Admin; controller gửi prop `labels`.
- `ExtensionFilters`/`ExtensionFormSections` dùng `Select`/`DateInput` (dữ liệu plugin khai báo không đổi).

### Đổi
- Trang danh sách sản phẩm: prop `products.links` thay bằng `products.current`/`products.last` (listener `vani.admin.page.catalog.products.index` đọc `links` cần sửa).
- Nhãn trạng thái đơn chuyển từ `ordering::messages` sang `ordering::labels`.
- `Insights/types` `formatValue(value, format, rules)` nhận thêm quy tắc tiền; component dùng `useFormatValue()`.

## 0.3.55 — 2026-11-04

Bộ component Admin ([ADR-039](../19-adr/ADR-039-admin-ui-reka.md)). Chỉ thêm, trừ một thay đổi hiển thị của `FlashMessage`.

### Thêm
- Admin JS `@admin/Components`: `DataTable` (cột + slot `cell-<key>`, nhận `extensions` để thêm cột và ô chọn dòng của plugin, `v-model:selected`), `Pagination` (`current`/`last`, sự kiện `change`), `Select` (`emptyLabel` cho lựa chọn "tất cả" = chuỗi rỗng), `Combobox` (gõ để lọc), `StatusBadge` (`label` + `tone`), `MoneyText`; `ConfirmDialog` và `Toaster` gắn sẵn trong `AdminLayout`.
- `@admin/confirm`: `confirmAction({ title, message?, confirmLabel?, tone?, reason? })` → `Promise<string | false>` thay `confirm()`/`prompt()`. `@admin/money`: `useMoney()`, `formatMoney(amount, rules)`.
- Shared props `app.money` (`MoneyFormatter::rules()`): định dạng tiền của cửa hàng cho client.
- Trang Admin của plugin chỉ dùng `@admin/Components`, không import `reka-ui` trực tiếp (arch test `AdminUiTest`).

### Đổi
- `FlashMessage` chỉ còn hiện lỗi chung; thông báo thành công (`flash.success`) hiện bằng toast của `AdminLayout`. Trang plugin đang đặt `FlashMessage` không cần sửa.
- `ExtensionActions`: thao tác có `confirm: true` hỏi bằng hộp xác nhận của Admin thay `confirm()` của trình duyệt.

## 0.3.54 — 2026-11-03

**Có thể ảnh hưởng thứ tự chạy** — priority mặc định của hook đổi từ 10 sang 100.

### Đổi
- Priority mặc định cho mọi loại hook (filter, action, validate, slot) và mọi cách đăng ký (`PluginServiceProvider::on*`, `Hook::on*`, `vani_add_*`, `add_hook_*`) là `HookManager::DEFAULT_PRIORITY` = **100** (trước: 10). Số nhỏ vẫn chạy trước. Plugin đặt priority tường minh **nhỏ hơn 100** để chạy *sau* listener mặc định (vd. 20) giờ sẽ chạy *trước* — cộng thêm 90 để giữ thứ tự cũ (20 → 110, 5 → 95). Core đã chỉnh: panel đơn Admin Giao hàng 5 → 95, Đổi/trả 20 → 110. Plugin trong `custom/plugin` không đặt priority tường minh nên không bị ảnh hưởng.

### Thêm
- Helper `vani_add_slot()` / `add_hook_slot()`: chèn nội dung vào slot giao diện từ bất kỳ đâu, listener gắn plugin theo vị trí file như `vani_add_*`.
- `vanishop.hooks.trace` (`VANI_HOOKS_TRACE`, mặc định theo `APP_DEBUG`): log debug mỗi lần gọi hook kèm plugin đang nghe.

## 0.3.53 — 2026-11-03

Chỉ thêm. Seeder của plugin ([plugin-system §7](../05-plugin/plugin-system.md)).

### Thêm
- Quy ước `Database/Seeders/` trong plugin: class kế thừa `Illuminate\Database\Seeder`, namespace theo provider (`Plugin\<Tên>\Database\Seeders`). Cài plugin (CLI và Admin) chạy migration **rồi** seeder; nâng cấp chạy seeder mới. Mỗi seeder một lần cho mỗi lần cài, transaction riêng, ghi `plugin_seeders`. Seeder phải idempotent và không dùng binding của plugin (provider chưa nạp lúc cài).
- Lệnh `vani:plugin:seed {plugin} [--rerun]`; doctor `seeders_pending`; audit `extension.plugin.seeded`, trường `seeded` trong audit cài/nâng cấp.

## 0.3.52 — 2026-11-03

Chỉ thêm. Admin → Plugin quản lý được vòng đời và cấu hình ([plugin-system §5b](../05-plugin/plugin-system.md)).

### Thêm
- Manifest `vanishop.json` có thêm trường tuỳ chọn: `description` (chuỗi hoặc `{locale: nội dung}`), `author` (đã có trong ví dụ, nay được đọc), `icon` (đường dẫn tương đối tới file `svg`/`png`/`webp`/`jpg` trong thư mục plugin, ≤ 256 KB; đường dẫn có `..` → manifest không hợp lệ).
- Admin → Plugin: chi tiết `/{admin}/plugins/{id}`, cài/bật/tắt (thường, ngừng an toàn, tắt ngay có xác nhận)/gỡ (giữ hoặc xoá dữ liệu, xoá phải gõ lại id)/nâng cấp — gọi `PluginManager`, cùng kiểm tra và audit với `vani:plugin:*` (quyền `extension.plugins.manage`).
- Trang cấu hình riêng `/{admin}/plugins/{id}/settings` cho cấu hình plugin khai báo bằng `SettingDefinition` (namespace = id plugin, quyền `settings.manage`) — không cần tự làm màn hình cấu hình.
- Màn hình Admin plugin thêm vào menu (`AdminNavigation::add(..., pluginId)`) hiện trong trang chi tiết plugin.

## 0.3.51 — 2026-11-03

Chỉ thêm. Quản trị nhân viên, vai trò, quên mật khẩu Admin ([reference-parity §3.3](../20-roadmap/reference-parity-refactor.md), R1).

### Thêm
- `Identity\Contracts\StaffRejected` (lỗi nghiệp vụ 422): `identity.staff_self`, `identity.last_owner`, `identity.exceeds_own_permissions`, `identity.role_in_use`, `identity.system_role`.
- Admin → Hệ thống → Nhân viên (`admin.staff.*`) và Vai trò (`admin.staff.roles.*`), quyền `staff.manage`. Quyền plugin đăng ký qua `PermissionRegistry` tự hiện trong màn hình vai trò, nhóm theo tiền tố mã (`plugin.*` → nhóm `plugin`).
- Trạng thái nhân viên `invited` (đã mời, chưa đặt mật khẩu) và `locked` (bị khoá); chỉ `active` đăng nhập được.

## 0.3.50 — 2026-11-03

Chỉ thêm (dữ liệu có thể là `null` khi cửa hàng bật ẩn giá). Ẩn giá với khách chưa đăng nhập ([reference-parity §3.2](../20-roadmap/reference-parity-refactor.md), R1).

### Thêm
- Cấu hình `core`/`storefront.price_visibility` (`public` | `members`, mặc định `public`).
- `ProductCards::forIds()`, Storefront API `/products`, `/products/{slug}`, dữ liệu thẻ/PDP mà enricher `product_card`/`product` và slot storefront nhận: trường `price_hidden` (bool). Khi `true`: `price` và `variants[].price` là `null` — plugin hiện giá/đo quảng cáo phải xử lý `null`.
- Storefront API: `/products` nhận Bearer token khách (`vani.customer:optional`); giỏ (`carts/{cart}`, `lines`) và checkout trả 403 `storefront.sign_in_to_view_prices` khi giá bị ẩn với người gọi. Middleware `vani.prices-visible` dùng được cho route plugin cần giá.
- `GET /_vani/gia?p[]={style_id}` trả `products` (khoảng giá theo sản phẩm) cho khách đã đăng nhập khi bật ẩn giá; `GET /_vani/phien` có `prices_url`. Theme: phần tử `[data-vani-price-for="{style_id}"]` được JS thay bằng giá.

## 0.3.49 — 2026-11-03

Chỉ thêm. Cấu hình vận hành storefront — bảo trì và khách vãng lai ([reference-parity §3.2](../20-roadmap/reference-parity-refactor.md), R1).

### Thêm
- `CheckoutRejected::signInRequired()` — mã `checkout.sign_in_required` (HTTP 403) khi cửa hàng tắt `core`/`storefront.guest_checkout` mà khách chưa đăng nhập.
- Cấu hình `core`/`storefront.maintenance`, `core`/`storefront.maintenance_message`: route storefront của plugin (`storefrontPages()`, `storefrontRoutes()`) trả 503 khi bảo trì; Storefront API trả lỗi `storefront.maintenance`. `webhookRoutes()` không bị ảnh hưởng.
- View theme `theme::pages.maintenance` (biến `$message`); theme con override được.

## 0.3.48 — 2026-11-03

Chỉ thêm. Hook hoá đợt 2 ([ADR-036](../19-adr/ADR-036-service-result-hooks.md)).

### Thêm
- Điểm tự động `vani.event.*`: mọi domain event Core (`Modules\*\Events\*`) thành hook action `vani.event.<module>.<event>`, tham số là đối tượng event, lỗi listener bị bỏ qua.
- Hook filter `vani.service.catalog.products_by_ids`, `vani.service.catalog.brands`, `vani.service.catalog.brand`, `vani.service.payment.view`, `vani.service.returns.view`.

### Sửa
- Hook kiểu action khai báo `on_error: skip` giờ bỏ qua listener lỗi (trước: chỉ filter áp dụng).

## 0.3.47 — 2026-11-03

Chỉ thêm. Hook tại kết quả service theo phong cách BeikeShop ([ADR-036](../19-adr/ADR-036-service-result-hooks.md), [catalog §4.2](extension-point-catalog.md)).

### Thêm
- Helper `hook_filter()`, `hook_action()`, `add_hook_filter()`, `add_hook_action()` — bí danh của `vani_*`, cùng HookManager.
- Hook filter `vani.service.checkout.totals` (`TotalsContext`, trước guard), `vani.service.checkout.quote`, `vani.service.pricing.prices`, `vani.service.promotion.result`, `vani.service.catalog.search`, `vani.service.catalog.product_detail`, `vani.service.catalog.category_tree`, `vani.service.ordering.order_detail`, `vani.service.customer.profile_attributes`, `vani.service.notification.request`, `vani.service.fulfillment.shipment_data`.
- `TotalsPipeline::GUARD_PRIORITY = 900`.

## 0.3.46 — 2026-10-29

Chỉ thêm. Liên kết sản phẩm và video sản phẩm, phỏng theo module Links/Video của Vanilo (MIT) ([ADR-035](../19-adr/ADR-035-product-links-videos-from-vanilo.md)).

### Thêm
- Extension point `Catalog\Contracts\VideoDriver` (tag `catalog.video-driver`, kind manifest `video`): `code()`, `label()`, `inputHint()`, `reference(input)` (chuẩn hoá link/ID, null = sai), `watchUrl()`, `embedUrl()` (src iframe https, null = không nhúng), `thumbnail()`; DTO `Data\VideoThumbnail`. Không có mặc định trong Core; tham chiếu: plugin `vani.video-youtube`.
- `CatalogReader::productDetail()` có thêm `videos` (chỉ video của driver đang bật) và `links` (`list<{code, name, style_ids}>`); `ProductViews::detail` (Storefront API `GET /products/{slug}`, hook `vani.storefront.pdp.*`) đổi `links[].style_ids` thành `links[].products` (thẻ sản phẩm, đã lọc sản phẩm không hiển thị).

## 0.3.45 — 2026-10-14

Chỉ thêm.

### Thêm
- `ProductImport::$replaceImages` (mặc định `false`): `true` thì màu đã có ảnh được thay bằng ảnh nhập (trước: chỉ thêm ảnh cho màu chưa có ảnh). `vani.demo-catalog` dùng qua `vani:demo:catalog --refresh-images`.

### Sửa
- Thư viện ảnh: tải lên ảnh trùng checksum với bản ghi có sẵn mà **file đã mất trên disk** thì ghi lại file đúng đường dẫn cũ (trước: trả bản ghi cũ, ảnh hỏng mãi).

## 0.3.44 — 2026-10-14

Chỉ thêm.

### Thêm
- `PluginServiceProvider::adminFormSection(…, array|Closure $fields, …)` (và `AdminExtensions::section`): trường có thể khai báo bằng closure trả `list<FieldDefinition>`, chỉ tính khi mở/lưu form — cho lựa chọn đọc từ DB (vd. danh sách bảng size của `vani.size-chart`) mà không truy vấn lúc boot.

## 0.3.43 — 2026-10-14

M1-core G1–G7 ([kế hoạch chuyển plugin §5.1](../05-plugin/vanicommerce-plugin-migration.md)). Chỉ thêm.

### Thêm
- Service contract `Storefront\Contracts\ProductCards::forIds(list<int> $styleIds, string $locale)` — thẻ sản phẩm (cùng dạng item lưới, có `extensions` của enricher `product_card`) theo id style, giữ thứ tự, tối đa `MAX_IDS = 60`.
- `CatalogReader::productsByIds(list<int>, locale, now)` — item catalog theo id style, chỉ sản phẩm đang hiển thị (Core là implementation duy nhất).
- Storefront API: `GET /api/storefront/v1/products?ids=…` (tối đa 30).
- `OrderStatistics::topStyles(from, to, limit = 20): list<array{style_id, quantity}>` — G3; id style lấy từ `order_lines.meta.style_id`, số lượng sau huỷ một phần, bỏ đơn huỷ và đơn đổi hàng (Core là implementation duy nhất).
- Tài nguyên Admin `category`, `brand` (G4): `adminFormSection('category'|'brand', …)` hiện trong form danh mục và form thương hiệu (trang danh sách), lưu cùng transaction với Core. Prop Inertia trang thương hiệu `extensionSections` là map `new` / id thương hiệu → phần form. Cột, bộ lọc, thao tác, tab cho hai tài nguyên này: chưa nối.
- Slot `vani.storefront.layout.body_start` (đầu `<body>`, trước header) và `vani.storefront.plp.before_listing` (args: ngữ cảnh `{type, category, brand}`, listing) — G5; theme `vani-base` render cả hai.
- Sự kiện trình duyệt `vani:cart-updated` (G6) sau thay đổi giỏ native; `/_vani/phien` thêm khoá `cart_change`.
- `SettingDefinition::$rules` (G7) và khoá `rules` trong `PluginServiceProvider::settings()` — rule validate Laravel cho giá trị sau ép kiểu. Giá trị theo ngôn ngữ: quy ước một khoá mỗi ngôn ngữ (`<khoá>.<locale>`).
- Chi tiết sản phẩm (`CatalogReader::productDetail`, Storefront API): `breadcrumb[].id`; thẻ và chi tiết sản phẩm: `brand.id`.

## 0.3.42 — 2026-10-10

Luật số điện thoại Việt Nam ra khỏi Core thành plugin hệ thống `vani.phone-vn` (ADR-029).

### Thêm
- Extension point `Shared\Contracts\PhoneNumberPolicy` (`TAG = vani.phone.policies`; `code()`, `normalize(string): ?string` → E.164, `national(string $e164)`). Bắt buộc đúng 1, chọn bằng `vanishop.locale.phone_policy` (mặc định `vn`); Core có `international` (chỉ nhận `+…`/`00…`).
- `Shared\Support\Phones::parse()` / `fromString()` — đọc số người dùng nhập theo policy đang chọn.
- Plugin hệ thống `vani.phone-vn` (bundled) — `vn`: 0912…, 84…, +84…, số bàn 02….

### Đổi (không thuộc snapshot, plugin trong repo đã cập nhật)
- `Shared\Domain\Phone\PhoneNumber`: bỏ `fromString`/`tryFromString` (luật VN); dựng bằng `PhoneNumber::of(e164, national)` hoặc `Phones`. `national()`, `masked()`, `equals()` giữ nguyên.
- `vani.sms-brandname` tự đổi +84 → 0… theo giao thức eSMS; số ngoài VN → `esms.unsupported_number`.
- `vani:plugin:doctor`: cảnh báo `bundled_not_installed` khi plugin hệ thống chưa cài; quy trình deploy chạy `vani:install` thay cho `migrate --force` ([production §5](../18-operations/production.md)).

## 0.3.41 — 2026-10-10

Thông số thị trường và vận hành ra `config/vanishop.php` (Core không ghi cứng múi giờ, ngôn ngữ, tiền tệ, định dạng, rate limit…). Bảng khoá: [production §2](../18-operations/production.md).

### Thêm
- `Shared\Support\StoreClock` (`timezone()`, `format($at, 'date'|'datetime'|'datetime_seconds'|'short_datetime')`, `toInput`, `fromInput`, `now`) và `StoreLocale` (`default()`, `supported()`, `options()`, `isSupported()`) — plugin và theme dùng thay cho giá trị ghi cứng.
- `MoneyFormatter`: constructor nhận dấu phân cách/ký hiệu (mặc định trung lập `1,250,000 VND`), container dựng theo `vanishop.locale.money`; `formatAmount(int, string)`.
- Prop Inertia `app.locales` (`{code, label}`), `app.defaultLocale` — form bản dịch của Admin theo ngôn ngữ cấu hình.

### Đổi
- `Notification\Contracts\Data\Recipient::$locale`: `?string`, mặc định `null` = ngôn ngữ mặc định của cửa hàng (trước: `'vi'`).
- `ReportPeriod`: `$timezone` mặc định theo `vanishop.locale.timezone`; `ReportPeriod::TIMEZONE` deprecated (gỡ ở 1.0).
- Validate bản dịch catalog: bắt buộc ngôn ngữ mặc định, chỉ nhận ngôn ngữ trong `locale.supported`.
- **Sửa lỗi**: khung giờ bảng giá nhập/hiển thị theo giờ cửa hàng và lưu UTC (trước: giờ nhập bị lưu như UTC — lệch 7 giờ ở VN).
- `PluginManifest`: `name` dạng chuỗi dùng cho mọi ngôn ngữ (không còn gán `vi`).

### Arch test
- `MicrokernelTest`: `modules/` không có literal múi giờ, mã ngôn ngữ, tiền tệ, định dạng ngày của cửa hàng (trừ bảng ISO 4217 và hằng deprecated).

## 0.3.40 — 2026-10-10

Microkernel: kiểm tra vận hành do module đăng ký cho doctor; trần giảm giá trung lập trong Core. Chỉ thêm.

### Thêm
- `Extensions::doctorCheck(string $code, callable(): ?string)`, `doctorChecks()` — module (hoặc plugin) đăng ký kiểm tra; `vani:plugin:doctor` báo cảnh báo mã `$code` (plugin `core`), kiểm tra ném lỗi → lỗi `doctor_check_failed`. Extension không biết nghiệp vụ của kiểm tra.
- Payment đăng ký `collect_on_delivery_missing`: không có cổng `collectsOnDelivery` nhận giao dịch mới → đổi hàng phải bù chênh sẽ bị từ chối.

### Đổi hành vi
- Không cấu hình `vanishop.promotion.max_discount_bp` → không giới hạn (`PromotionEvaluator::NO_CAP_BP` = 10000) thay vì 50%. Cấu hình cửa hàng giữ 5000 (NĐ 81/2018), hành vi hiện tại không đổi.

## 0.3.39 — 2026-10-10

Củng cố microkernel ([ADR-029](../19-adr/ADR-029-commerce-microkernel.md)): vòng 0 không còn biết tên tài nguyên thương mại, Core không còn gắn mã cổng/thuế của plugin hệ thống. Chỉ thêm + một hằng deprecated.

### Thêm
- `AdminScreen::declareResource(string)`, `resources()` — module sở hữu màn hình Admin khai báo tài nguyên mở rộng được (Catalog `product`, Ordering `order`, Customer `customer`), gọi trong `register()`. Plugin vẫn gọi `admin*` trong `boot()` như trước.
- `Payments::collectOnDeliveryGateway(): ?string` — cổng có `collectsOnDelivery` đang nhận giao dịch mới; `Payments::offlineGateways(): list<string>` — cổng thu khi giao hoặc xác nhận thủ công.

### Đổi hành vi
- Đơn đổi hàng phải bù chênh thu qua cổng `collectOnDeliveryGateway()` thay vì mã `cod` cố định; không có cổng như vậy → `ReplacementUnavailable` lý do `no_collect_on_delivery` (yêu cầu đổi giữ `received`).
- Cảnh báo `payments.failure_rate` loại cổng theo capability; bỏ cấu hình `vanishop.alerts.offline_gateways`.
- Mặc định dự phòng của `core.tax.calculator` trong Core là `none`; mặc định của cửa hàng vẫn ở `vanishop.tax.calculator` (`VANI_TAX_CALCULATOR`, `vn_vat_inclusive`).

### Deprecated
- `AdminScreen::RESOURCES` — không còn dùng để kiểm tra; đọc `resources()`. Gỡ ở 1.0.

### Arch test
- R29: vòng 0 (`Shared`, `Tenancy`, `Identity`, `Extension`) không dùng module thương mại (trừ `Tests/`).
- R28: `modules/` không chứa literal trùng mã `code()` của `PaymentGateway`/`TaxCalculator`/`ShippingCarrier` do plugin cung cấp hay id plugin.

## 0.3.38 — 2026-10-08

Sidebar Admin dạng accordion. Chỉ thêm (tham số tuỳ chọn cuối).

### Thêm
- `PluginServiceProvider::adminMenu(..., string $group = 'extensions')` — nhóm menu: `sales`, `catalog`, `marketing`, `content`, `system`, `extensions` (mặc định, "Mở rộng"). Nhóm chưa đăng ký → `extensions`.
- `AdminNavigation::group(key, label, order)`, `visibleGroups()`; `add(..., ?string $group)`. Mục trong prop Inertia `navigation` có thêm `group`; prop mới `navigationGroups`.

## 0.3.37 — 2026-10-08

Tầng giá trên đơn (roadmap Phase 8, [order §2.2](../09-order/order.md)). Chỉ thêm.

### Thêm
- `Ordering\Contracts\Data\PriceBreakdown`; `OrderReader::priceBreakdown(int)`; `OrderDetail::$pricing`; `OrderStatistics::orderIdsWithPriceLists(list<string>)`.
- `order_lines.price_list_code`; `OrderLineDraft::$priceListCode`, `TotalsLine::$priceListCode`, `CartLineView::$priceListCode` (tham số tuỳ chọn cuối).
- Payload `vanishop.order.v1` thêm `pricing` (schema cập nhật).

## 0.3.36 — 2026-10-08

Campaign (roadmap Phase 8). Chỉ thêm.

### Thêm
- `Pricing\Contracts\PriceListSchedule` (`schedulable`, `schedule`) — điều khiển lịch/bật-tắt bảng giá sale/member từ module khác.
- `OrderStatistics::summarize(list<int> $orderIds): SalesTotals` (Core là implementation duy nhất).
- Campaign trong module Promotion: `campaigns`, `promotions.campaign_id`, `campaign_price_lists`; Admin `/{admin}/promotion/campaigns`.

## 0.3.35 — 2026-10-08

Phân khúc khách + giá thành viên (roadmap Phase 8). Chỉ thêm.

### Thêm
- `Pricing\Contracts\CustomerGroupDirectory` (`groupOf`, `groups`) — module Customer cung cấp; `PricingContext::$customerId` (PriceResolver tự tra nhóm khi `customerGroupId` chưa đặt).
- `Customer\Contracts\CustomerSegments` (`segmentOf` → `CustomerSegment{groupId, groupCode, groupName, tags}`, `groups`).
- Storefront: `GET /api/storefront/v1/me/prices?variant_ids[]=`, native `GET /_vani/gia?v[]=` (chỉ variant có giá nhóm thấp hơn giá chung).
- `vani.promotion-rules` 0.3.0: rule `in_customer_groups` (`groups`: mã nhóm), `customer_has_tags` (`tags`, `match` any|all).

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
