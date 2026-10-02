# Mở rộng Extension Points của Commerce Kernel (EP v2)

> Trạng thái: **Designed**, ADR Accepted 2026-10-02. Quyết định: [ADR-030](../19-adr/ADR-030-extension-surface-v2.md). Nền: [ADR-029](../19-adr/ADR-029-commerce-microkernel.md) (microkernel), [extension-model](extension-model.md) (bốn cơ chế, compatibility policy), [extension-point-catalog](extension-point-catalog.md) (danh mục hiện có).
>
> Clean-room: phần "hệ tham chiếu" dưới đây là đầu ra của vai trò nghiên cứu trên BeikeShop v3.0.0.11, chỉ ghi **khái niệm** bằng lời của VaniShop; không chép tên hook, class, bảng hay cấu trúc file ([clean-room](../01-principles/clean-room-license.md)).

## 1. Mục tiêu

Mọi capability trong [plugin-catalog](../05-plugin/plugin-catalog.md) (P1 → Later) phải **viết được bằng plugin, không sửa `modules/`**. Hiện các extension point đủ cho capability "thay thế" (cổng thanh toán, hãng, thuế, rule khuyến mãi, kênh gửi) nhưng thiếu cho capability "bổ sung" — loại cần thêm dữ liệu, màn hình, thao tác và trang riêng (loyalty, hoá đơn điện tử, size chart, wishlist, omnichannel, marketplace).

Thước đo: sau EP v2, bảng §7 không còn ô "cần sửa Core".

## 2. Hệ tham chiếu làm gì (khái niệm)

| # | Khái niệm quan sát được | Đánh giá cho VaniShop |
|---|---|---|
| T1 | Gần như **mọi màn hình quản trị** (danh sách, form, chi tiết) đều có ba điểm mở: lọc dữ liệu đưa ra view, hành động sau khi lưu, và điểm bọc quanh từng khối giao diện. Nhờ đó plugin thêm trường/cột/tab vào Admin mà không sửa view. (≈ 150 điểm lọc + ≈ 60 điểm sau-lưu ở Admin, tương tự cho Admin API) | **Học**: đây là khoảng trống lớn nhất của VaniShop. Nhưng làm bằng **registry có kiểu** thay vì lọc mảng dữ liệu tự do (§4.A) |
| T2 | Dữ liệu trả ra API khách đi qua bộ chuyển đổi tài nguyên có điểm lọc → plugin bổ sung khoá | **Học** có giới hạn: chỉ được thêm dưới không gian tên của plugin, có batching (§4.B) |
| T3 | Tổng đơn là chuỗi dịch vụ tính dòng, plugin thêm dòng | **Đã có, chặt hơn** (`TotalsCalculator`, guard cuối) |
| T4 | Danh sách quyền, menu, tab cấu hình đều lọc được | **Đã có** dạng registry (`permissions()`, `adminMenu()`, `settings()`) |
| T5 | Theme có ~90 điểm bọc quanh khối (trang sản phẩm, tài khoản, checkout, header, footer) | **Học số lượng và vị trí**; giữ quy tắc "chỉ nối thêm" của ADR-025, mở rộng danh mục slot (§4.B) |
| T6 | Plugin được phân loại (thanh toán, vận chuyển, marketing, theme, phân tích, CSKH, ngôn ngữ, khác) | **Học**: chuẩn hoá `kind` trong manifest để Admin lọc/nhóm và để Extension kiểm tra contract tối thiểu theo loại (§4.H) |
| T7 | Plugin "dịch máy" (nhà cung cấp dịch) và bộ công cụ cho trợ lý AI do plugin đăng ký | **Học ở mức contract**, đợt Later (§4.I) |
| T8 | Plugin sửa danh sách trạng thái và bảng chuyển của máy trạng thái đơn | **Không áp dụng** (extension-model §2; từng gây rò kho ở hệ tham chiếu) |
| T9 | Plugin thêm cột vào bảng Core, thêm quan hệ động vào model | **Không áp dụng** ([ADR-027](../19-adr/ADR-027-plugin-data-no-core-columns.md)) |
| T10 | Điểm lọc nhận mảng tự do (không kiểu), đặt theo tên controller/route | **Không áp dụng**: điểm mở rộng của VaniShop đặt theo **khái niệm nghiệp vụ** (resource), có DTO và kiểm kiểu, sống qua việc đổi controller |

## 3. Bản đồ bề mặt mở rộng

Một capability nghiệp vụ hoàn chỉnh cần chạm tới tám bề mặt. Bảng dưới đánh giá Core hiện tại.

| Bề mặt | Plugin cần làm | Đã có | Còn thiếu (EP v2) |
|---|---|---|---|
| **S1 Dữ liệu** | Lưu dữ liệu riêng gắn với thực thể Core | Bảng `plg_*`, `meta` JSON trên `orders`, `styles`… | `meta` trên `cart_lines`, `order_lines`; quy tắc snapshot dòng giỏ → dòng đơn (§4.C) |
| **S2 Luồng giao dịch** | Chặn/bổ sung khi thêm giỏ, tính tổng, đặt hàng | `cart.validate_line`, `TotalsCalculator`, `checkout.*_validate`, `order.before_create/after_create` | Tuỳ chọn dòng giỏ, thuộc tính ngữ cảnh khuyến mãi, bước thanh toán nhiều bước (§4.C) |
| **S3 Sự kiện** | Phản ứng khi việc đã xảy ra | 25 event sau commit | `OrderCompleted`, `CartAbandoned`, `PaymentAuthorized`, `ProductDeleted`, `CustomerUpdated`, `PluginEnabled/Disabled` (§4.D) |
| **S4 Admin UI** | Thêm trường vào form, cột vào danh sách, tab, nút thao tác, trang riêng | Menu, trang Inertia riêng, slot dashboard + sidebar đơn, settings | Mở rộng màn hình **của Core** theo resource (§4.A) |
| **S5 Storefront** | Thêm dữ liệu vào sản phẩm/giỏ/đơn, UI, trang, API riêng | 11 slot, theme con | Làm giàu dữ liệu, thêm slot (tài khoản, header, bộ lọc, checkout), route/trang của plugin (§4.B) |
| **S6 Danh tính** | Đăng nhập kiểu mới, phạm vi quyền kiểu mới | OTP, mật khẩu; scope owner/location | `AuthProvider`, loại scope do plugin khai báo (§4.E) |
| **S7 Tích hợp & vận hành** | Message mới ra/vào, tác vụ nền, sức khoẻ tích hợp | `Connector`, `InboundHandler`, `schedule()`, webhook route | Loại message + schema, báo cáo sức khoẻ plugin (§4.G) |
| **S8 Plugin ↔ plugin** | Plugin làm nền cho plugin khác | `requires.plugins`, resolver | Plugin công bố hook/contract có kiểm soát (§4.H) |

## 4. Thiết kế

Quy ước chung cho mọi điểm mới (bổ sung [extension-model §4.4](extension-model.md)):

- **Đặt theo resource nghiệp vụ**, không theo controller: `product`, `variant`, `category`, `brand`, `customer`, `order`, `promotion`, `location`, `shipment`, `return`.
- **Dữ liệu plugin luôn có không gian tên** `extensions.<plugin-id>` (API) hoặc `meta.<plugin-id>` (DB). Core kiểm tra: plugin chỉ ghi vào khoá của mình.
- **Batching bắt buộc** ở mọi điểm chạy trên danh sách: callback nhận list id, trả map — không N+1.
- **Ngân sách thời gian**: điểm đồng bộ trong request đọc ≤ 50 ms/plugin (đo sẵn bằng `HookManager`), không I/O mạng trong transaction.
- Mỗi điểm có implementation tham chiếu + contract test (R26) và dòng trong [CHANGELOG-extension](CHANGELOG-extension.md).

### 4.A Admin: mở rộng màn hình của Core (S4)

Học T1 nhưng thay "lọc mảng dữ liệu view" bằng **registry khai báo** trên `PluginServiceProvider`. Admin Vue render chung từ khai báo, plugin không cần viết JS cho trường hợp phổ biến.

| Registry | Vị trí | Ví dụ plugin |
|---|---|---|
| `adminFormSection(resource, key, label, fields, load, save, order)` | Form tạo/sửa resource | Size chart (product), mã số thuế (customer), SEO nâng cao (category/brand) |
| `adminColumn(resource, key, label, resolve, filter?, sortable?)` | Bảng danh sách | Hạng thành viên (customer), số HĐĐT (order), điểm rủi ro COD (order) |
| `adminAction(resource, key, label, permission, handle, scope: row\|bulk\|detail, confirm?)` | Nút trên chi tiết / chọn nhiều | Xuất lại HĐĐT, gửi lại ZNS, đẩy đơn sang sàn |
| `adminTab(resource, key, label, rows)` | Tab trên trang chi tiết (dòng nhãn/giá trị; UI phức tạp → trang riêng `adminPages`) | Lịch sử điểm (customer), seller (product) |
| `adminFilter(resource, key, label, options, apply)` | Bộ lọc danh sách; `apply` trả **id** thoả — plugin không chạm truy vấn của Core | "Đơn có HĐĐT lỗi" |

```php
// custom/plugin/SizeChart/SizeChartServiceProvider.php
$this->adminFormSection('product', 'size_chart', 'Bảng size',
    fields: [FieldDefinition::select('chart_id', 'Bảng size', optionsFrom: fn () => $this->charts->options())],
    load: fn (int $styleId): array => $this->charts->forStyle($styleId),          // dữ liệu hiện tại của plugin
    save: fn (int $styleId, array $values) => $this->charts->assign($styleId, $values['chart_id'] ?? null),
);
$this->adminColumn('order', 'einvoice', 'HĐĐT',
    resolve: fn (array $orderIds): array => $this->invoices->numbersFor($orderIds), // batch: id => giá trị
);
$this->adminAction('order', 'einvoice.reissue', 'Xuất lại HĐĐT', 'einvoice.manage',
    handle: fn (int $orderId) => $this->invoices->reissue($orderId), scope: 'detail', confirm: true,
);
```

| Quy tắc | Lý do |
|---|---|
| `fields` dùng `FieldDefinition` (string, text, int, bool, select, date; multiselect/money khi có plugin cần) → validate phía server trước khi gọi `save`. Input đặt dưới `extensions.<id plugin dạng slug>` (`vani-hello-world`) vì dấu chấm là phân cấp trong validate | Không cần JS; lỗi hiển thị như lỗi form Core |
| `save` chạy **trong transaction lưu resource của Core**, sau Core; lỗi → rollback cả form | Không có trạng thái nửa vời |
| Plugin cần UI phức tạp → `component` Inertia của plugin (`adminPages()` đã có) thay cho `fields` | Lối thoát có kiểm soát |
| `adminAction` kiểm tra quyền + ghi audit (`plugin.action`) do Core làm | Plugin không tự bỏ qua quyền |
| Core **không** cho plugin ẩn/đổi trường của Core | Chỉ nối thêm, như slot |

Admin API v1 ([api](../06-api/api.md), Designed) dùng **cùng** khai báo: `GET /api/admin/v1/products/{id}` trả `extensions.<plugin-id>` từ `load`, `PATCH` nhận cùng khoá rồi gọi `save` — không cần hook riêng cho API như hệ tham chiếu.

### 4.B Storefront: dữ liệu, slot, trang của plugin (S5)

**Làm giàu dữ liệu** (học T2): contract `StorefrontEnricher`, chạy ở Presenter nên **native và Storefront API nhận cùng dữ liệu**.

```php
interface StorefrontEnricher
{
    public const TAG = 'vani.storefront.enrichers';

    /** Resource áp dụng: product_card | product | cart | order | brand | category */
    public function resource(): string;

    /**
     * @param  list<array<string, mixed>>  $items  DTO đã trình bày (đọc, không sửa)
     * @return array<int|string, array<string, scalar|array|null>>  id => dữ liệu, Core gắn vào extensions.<plugin-id>
     */
    public function enrich(array $items, string $locale): array;
}
```

Core gọi qua `Extensions::call()` (lỗi → bỏ dữ liệu của plugin đó, trang vẫn chạy), chỉ gắn kết quả vào `extensions.<plugin-id>`. Ví dụ: số sao đánh giá trên thẻ sản phẩm, điểm dự kiến trên PDP, nhãn "freeship" theo rule.

**Thêm slot** (học T5), khai báo trong `modules/Storefront/hooks.php` cùng view `vani-base`:

| Slot mới | Vị trí |
|---|---|
| `vani.storefront.header.nav` / `header.actions` | Menu chính / cụm icon (wishlist, điểm) |
| `vani.storefront.footer.columns` | Cột footer (chính sách, đăng ký nhận tin) |
| `vani.storefront.plp.filters` | Bộ lọc bổ sung trên trang danh sách |
| `vani.storefront.pdp.gallery_after` | Dưới ảnh (video, 360°) |
| `vani.storefront.checkout.contact_after`, `checkout.address_after`, `checkout.payment_after` | Trường bổ sung từng bước (dùng `extra[<plugin-id>]` đã có) |
| `vani.storefront.account.menu`, `account.dashboard`, `account.order_detail` | Tài khoản khách (cùng trang `/tai-khoan`) |

**Route và trang của plugin**:

| Registry | Đường dẫn | Ghi chú |
|---|---|---|
| `storefrontRoutes(file)` | `/api/storefront/v1/x/<plugin-slug>/…` | Middleware ngữ cảnh storefront + rate limit chung; xác thực khách tuỳ chọn |
| `storefrontPages(file)` + `storefrontViews(path, namespace)` | `/p/<plugin-slug>/…` (đường dẫn đẹp riêng: Designed) | View của plugin render trong layout theme (`theme::layouts.app`), theme override được view plugin qua `custom/theme/<theme>/plugins/<slug>/` |
| `accountPages(key, label, route)` | Mục trong `/tai-khoan` | Wishlist, điểm thưởng, ví |

### 4.C Luồng giao dịch: dòng giỏ có thuộc tính, ngữ cảnh khuyến mãi (S1–S2)

| Điểm | Thiết kế | Plugin dùng |
|---|---|---|
| `meta` trên `cart_lines` và `order_lines` | Cột JSON theo không gian tên plugin; dòng đơn **chụp** `meta` của dòng giỏ lúc đặt (bất biến sau đó) | Gói quà, khắc tên, bundle |
| `CartLineOption` (contract) | Plugin khai báo option nhận ở `POST /carts/{id}/lines` (`options.<plugin-id>.*`), validate + giá phụ thu (dương, tách thành adjustment qua `TotalsCalculator` của chính plugin) | Khắc tên +50.000đ |
| Hai dòng cùng variant khác option | Khoá dòng giỏ = (variant, hash(options)) thay cho unique variant | Bắt buộc khi có option |
| `vani.checkout.context` (filter) | Bổ sung `PromotionContext::$attributes` từ request (mã giới thiệu, nguồn chiến dịch) trước khi đánh giá khuyến mãi | Creator/affiliate, UTM |
| `PaymentGateway` có bước **authorize → capture** (interface tuỳ chọn `CapturesLater`) | Event `PaymentAuthorized`; capture khi giao/xác nhận | Thẻ quốc tế, BNPL |

Bất biến giữ nguyên: plugin không đổi giá niêm yết của dòng (chỉ thêm adjustment truy vết được), không đổi bảng chuyển trạng thái, không ghi ATS.

### 4.D Sự kiện vòng đời (S3)

Nguyên tắc: **mọi chuyển trạng thái có ý nghĩa nghiệp vụ của aggregate đều phát event** sau commit; payload gồm id + snapshot tối thiểu, chi tiết đọc qua reader contract.

| Event mới | Phát khi | Plugin chờ |
|---|---|---|
| `OrderCompleted` | Hết hạn đổi trả sau giao (job đã có, chưa phát event) | Loyalty (điểm khả dụng), HĐĐT, creator (hoa hồng) |
| `CartAbandoned` | Job phát hiện giỏ có khách/SĐT không hoạt động N phút | Abandoned cart |
| `PaymentAuthorized` | Cổng xác nhận giữ tiền | Thẻ, BNPL |
| `ProductDeleted` (nháp bị xoá), `CategoryChanged`, `BrandChanged` | CRUD catalog | Feed export, search ngoài |
| `CustomerUpdated` | Hồ sơ đổi | CRM, loyalty |
| `PluginEnabled` / `PluginDisabled` | Vòng đời plugin | Plugin phụ thuộc dọn cache |

### 4.E Danh tính (S6)

- `AuthProvider` (contract, tag `vani.customer.auth_providers`): bắt đầu đăng nhập (URL chuyển hướng), nhận callback → `ExternalIdentity(provider, subject, phone?, email?)`; Core ghép vào khách theo SĐT/email đã xác minh, phát `CustomerRegistered` nếu mới. Plugin: Zalo/Google/Facebook login.
- `scopeTypes()` (registry): plugin khai báo loại scope phân quyền mới (vd. `seller`) + resolver "đối tượng thuộc scope nào"; Identity giữ việc kiểm tra quyền (học từ ghi chú marketplace, không thêm khái niệm seller vào Core).

### 4.F Nội dung (S5)

- `StorefrontBlock` (đã thiết kế ở [storefront §4](../14-storefront/storefront.md)) + bảng `page_blocks`; plugin đóng góp block (lookbook, gợi ý sản phẩm).
- `MenuItemType` (contract): loại mục menu do plugin cung cấp (trang plugin, bộ sưu tập động), resolve URL + nhãn lúc render.

### 4.G Tích hợp và vận hành (S7)

- `integrationMessageTypes()` (registry): loại message mới + JSON Schema → outbox/inbox kiểm tra payload, Integration API công bố.
- `HealthCheck` (contract, tag `vani.health.checks`): plugin báo tình trạng kết nối (token hết hạn, quota) → Admin "Tích hợp" + `vani:plugin:doctor` hiển thị; chạy theo lịch, không chạy trong request khách.
- `ReportProvider` + `DashboardWidget` (đã liệt kê): báo cáo do plugin cung cấp, đọc qua contract đọc (`OrderReader`, read model) — để Reporting thành `vani.reports` ([commerce-kernel §6](../02-architecture/commerce-kernel.md)).

### 4.H Plugin làm nền cho plugin (S8)

- Plugin công bố `Plugin\<Name>\Contracts`, `Events`, và `custom/plugin/<Name>/hooks.php`; registry hook nạp file này **khi plugin đang bật** (hiện chỉ đọc `modules/*/hooks.php`). Tên hook bắt đầu bằng id plugin (`vani.loyalty.points.earned`).
- Arch test mở rộng R5: plugin B chỉ dùng `Contracts`/`Events` của plugin A khi khai báo A trong `requires.plugins`.
- Contract test của plugin nền đặt ở `Plugin\<Name>\Testing`.
- Manifest `kind` chuẩn hoá (học T6): `payment_gateway`, `shipping_carrier`, `shipping_rate`, `tax`, `marketing`, `analytics`, `customer_service`, `content`, `integration`, `theme_extension`, `language`, `feature`. Extension cảnh báo khi `kind` = `payment_gateway` mà plugin không đóng góp `PaymentGateway` (doctor).

### 4.I Later (học T7)

| Contract | Ý tưởng |
|---|---|
| `TranslationProvider` | Dịch nội dung catalog sang ngôn ngữ khác (bản nháp, nhân viên duyệt) |
| `AgentTool` | Thao tác an toàn do plugin công bố cho trợ lý AI (đọc báo cáo, tạo khuyến mãi nháp), chạy qua cùng kiểm tra quyền + audit như Admin |

## 5. Không làm (giữ bất biến)

| Đề xuất | Lý do |
|---|---|
| Plugin thêm trạng thái/chuyển trạng thái đơn, thanh toán, vận đơn | Bất biến Core (T8); cần trạng thái riêng → plugin giữ trạng thái của mình trong bảng `plg_*` |
| Lọc mảng dữ liệu view/route tự do | Không kiểu, gắn với controller (T10); dùng registry §4.A, enricher §4.B |
| Bọc/thay HTML của view | ADR-025; thay khối bằng override view trong theme |
| Thêm cột vào bảng Core | ADR-027 (T9) |
| Plugin sửa giá niêm yết, ATS, totals cuối | Chỉ adjustment truy vết được; strategy chỉ thu hẹp |

## 6. Lộ trình

Làm extension point **ngay trước** plugin đầu tiên dùng nó (R26: plugin đó là implementation tham chiếu). Đợt chỉ **thêm** thì tăng bản vá `0.3.x` (plugin `^0.3` chạy tiếp); cột Core dưới đây là dự kiến tối đa.

| Đợt | Extension point | Plugin tham chiếu | Core |
|---|---|---|---|
| **W1** ✅ (2026-10-02, Core 0.3.1) | Slot storefront mới (header, footer, bộ lọc PLP, ảnh PDP, checkout từng bước; slot tài khoản chờ trang `/tai-khoan`); `StorefrontEnricher` (tham chiếu `vani.hello-world`); `OrderCompleted` | `vani.tracking-pixels`, `vani.vnpay` (đã đủ EP) | 0.3.1 |
| **W2** ✅ (2026-10-02, Core 0.3.2) | `adminFormSection`, `adminColumn`, `adminAction`, `adminTab`, `adminFilter` + `FieldDefinition`, trên `product` (form, cột, lọc), `order` (cột, lọc, thao tác chi tiết + hàng loạt, tab), `customer` (cột, lọc, thao tác, tab). Tham chiếu: `vani.hello-world`. Tài nguyên khác + Admin API: khi có plugin cần | `vani.einvoice`, `vani.size-advisor` | 0.3.2 |
| **W3** ✅ (2026-10-02, Core 0.3.3) | `storefrontRoutes`, `storefrontPages` + `storefrontViews` (theme override), `CartAbandoned` (tham chiếu `vani.hello-world`). `accountPages` chờ trang tài khoản native (`/tai-khoan`, 12b) | `vani.wishlist`, `vani.abandoned-cart` | 0.3.3 |
| **W4** (giao dịch) | `meta` dòng giỏ/đơn, `CartLineOption`, `vani.checkout.context`, `CapturesLater` + `PaymentAuthorized` | `vani.product-bundle`, gói quà, `vani.creator` | 0.5 |
| **W5** (nền tảng) | Plugin `hooks.php`, `kind` chuẩn, `AuthProvider`, `scopeTypes()`, `HealthCheck`, `integrationMessageTypes()` | `vani.loyalty` → `vani.promotion-advanced`, `vani.social-login` | 0.6 |
| **W6** | `StorefrontBlock`, `MenuItemType`, `ReportProvider`/`DashboardWidget` | `vani.lookbook`, `vani.reports` | 0.6 |
| Later | `TranslationProvider`, `AgentTool` | — | — |

Mỗi đợt: cập nhật [extension-point-catalog](extension-point-catalog.md) (chuyển dòng từ §7 lên §1–§5), snapshot public API, CHANGELOG-extension, contract test.

## 7. Kiểm chứng: plugin dự kiến × extension point

| Plugin | Dùng (✅ có sẵn · W# đợt bổ sung) | Sửa Core? |
|---|---|---|
| `vani.vnpay`, `vani.momo` | ✅ `PaymentGateway`, webhook route | Không |
| `vani.tracking-pixels` | W1 slot `layout.head`/`order.after_summary` (✅ có), W1 enricher (giá trị đơn cho pixel) | Không |
| `vani.einvoice` (+ nhà cung cấp) | ✅ `extra[…]`, `order.before_create`; W1 `OrderCompleted`; W2 `adminColumn`/`adminAction`; W5 plugin công bố `EInvoiceProvider` | Không |
| `vani.cod-risk-guard` | ✅ `checkout.payment_methods`, `CheckoutValidator`; W2 `adminColumn` (điểm rủi ro) | Không |
| `vani.wishlist` | W3 `storefrontRoutes`/`accountPages`, W1 slot `header.actions`, ✅ events giá/tồn | Không |
| `vani.abandoned-cart` | W3 `CartAbandoned`, ✅ `NotificationChannel` | Không |
| `vani.loyalty` | ✅ `TotalsCalculator`, `order.after_create`; W1 `OrderCompleted`, enricher; W2 `adminTab`; W3 `accountPages`; W5 công bố hook | Không |
| `vani.size-advisor` | W2 `adminFormSection`, W1 slot `pdp.after_add_to_cart` (✅), W1 enricher | Không |
| `vani.product-bundle` | W4 `CartLineOption`, `meta` dòng, ✅ `TotalsCalculator` | Không |
| `vani.store-omnichannel` | Cần `FulfillmentMethod` + `ShipmentRecorder` (đã ở catalog §7, đợt P2) | Không (sau khi có 2 contract) |
| `vani.social-login` | W5 `AuthProvider` | Không |
| `vani.feed-export` | ✅ `CatalogReader`, `schedule()`; W1 events catalog | Không |
| `vani.marketplace`, `vani.creator` | ✅ `order.after_create`; W4 `checkout.context`; W5 `scopeTypes()`; W2 Admin | Không |
| `vani.reports` | W6 `ReportProvider`, `DashboardWidget` | Không |
