# Củng cố kiến trúc — Core nhỏ, Domain rõ ràng, Module độc lập, Plugin ổn định (K1–K4)

> Trạng thái: **Accepted** — Owner duyệt 2026-11-17 (Core 0.4.69), chọn đúng cả 7 đề xuất ở §5; làm K1 → K5, mỗi đợt một commit. Tiếp nối [extension-surface-v3](extension-surface-v3.md) (X1–X7 xong, 0.4.59–0.4.65).
>
> Liên quan: [commerce-kernel](../02-architecture/commerce-kernel.md) (bốn vòng, tiêu chí vào Core), [bounded-contexts](../02-architecture/bounded-contexts.md), [architecture-rules](../01-principles/architecture-rules.md) (R4–R11, R26–R29, R35), [extension-model §5](../04-extension/extension-model.md) (compatibility policy), [plugin-system §6.1](../05-plugin/plugin-system.md) (rà soát hợp đồng plugin).

## 1. Vì sao làm bây giờ

Hai tháng qua Core thêm nhiều điểm mở rộng: EP v2 (W1–W6), EP v3 (X1–X7), ba chốt có người (ADR-046), đặt trước (ADR-047). Plugin đã làm được phần lớn việc vận hành mà không sửa Core. Cái giá là **bề mặt hợp đồng phình nhanh hơn kỷ luật giữ nó**:

- Core tăng 11 phiên bản trong hai ngày (0.4.59 → 0.4.69), trong khi 24/29 plugin vẫn khai `requires.vanishop: ^0.4`. Ràng buộc này không bảo vệ được plugin.
- 21/44 tag extension point chưa có bộ contract test (R26 mới đạt nửa). 8 tag trong số đó do EP v3 thêm.
- Ranh giới module đúng ở phần lớn chỗ, nhưng còn vài lối tắt chưa test nào bắt được (§2.3).

Đợt này **không thêm tính năng**. Mục tiêu là giữ cho bốn tính chất dưới đây **đo được và có test giữ**, trước khi mở thêm điểm mở rộng hay go-live.

| Tính chất | Nghĩa trong VaniShop | Giữ bằng |
|---|---|---|
| **Core nhỏ** | Vòng 0 có API khai báo rõ; vòng 1 chỉ chứa primitive, bất biến và điểm mở rộng ([commerce-kernel §2](../02-architecture/commerce-kernel.md)); phần còn lại là plugin | Arch test vòng 0 + báo cáo kích thước theo đợt |
| **Domain rõ ràng** | Quy tắc nghiệp vụ nằm trong `Domain/` thuần PHP, test không cần DB; Application chỉ điều phối; Controller không truy vấn | Arch test R8/R9 chặt hơn + unit test Domain |
| **Module độc lập** | Phụ thuộc đi một chiều theo `config/modules.php`; chỉ qua `Contracts`/`Events`; không đọc/ghi bảng của module khác | Arch test thứ tự module + sở hữu bảng (R7, R11) |
| **Plugin ổn định** | Plugin biết trước khi nào Core làm vỡ nó; mỗi điểm mở rộng có test hợp đồng; plugin lỗi không kéo sập trang | Version API mở rộng tách khỏi Core, mức ổn định, contract suite, deprecation log, circuit breaker |

## 2. Hiện trạng đo được (2026-11-17, Core 0.4.69)

Số liệu lấy từ mã nguồn, không tính `Tests/` và migration.

### 2.1 Kích thước

| Hạng mục | Số liệu |
|---|---|
| Module Core | 18 module, khoảng **63,7k** dòng PHP. Lớn nhất: Catalog 10,9k · Storefront 7,9k · Extension 6,8k · Ordering 4,6k · Inventory 4,4k |
| Plugin trong repo | **29** plugin, khoảng 12,3k dòng |
| Public API (`public-api.snapshot`) | **291** class/interface (1.510 dòng). Plugin trong repo thực dùng **126** class `Contracts`/`Events` |
| Điểm mở rộng | 44 tag contract, 102 hook (đều `public`, 3 `experimental`) |

### 2.2 Domain

| Module | `Application/` (dòng) | `Domain/` (dòng) | Ghi chú |
|---|---|---|---|
| Ordering | 1.915 | 301 | Có state machine trong Domain — mẫu tốt |
| Inventory | 2.301 | 275 | `StockLevel`, `Allocation` thuần, có unit test |
| Pricing | 1.390 | 215 | |
| Fulfillment | 1.298 | 131 | `FulfillmentService` 426 dòng chứa luật chuyển trạng thái vận đơn, thu COD |
| Returns | 881 | 75 | `ReturnService` 589 dòng: số lượng ≤ đã giao, hạn đổi trả, tiền hoàn |
| Payment | 1.237 | 49 | `PaymentService` **804** dòng — file lớn nhất Core: trạng thái khoản, hoàn tiền, chuyển trả, đối soát |
| Customer | 1.505 | 45 | |
| Checkout | 1.054 | **0** | [bounded-contexts §4](../02-architecture/bounded-contexts.md) xếp Checkout là "rich domain" nhưng không có `Domain/` |

Controller: 24 controller gọi `::query()`/`DB::` trực tiếp. Arch test R9 hiện chỉ chặn facade `DB`, nên `Model::query()` lọt qua. Nặng nhất là `OrderController` (607 dòng), `ProductController` (456), `ShipmentController`, `ReturnController`. Không module nào có README ghi mức domain như [bounded-contexts §4](../02-architecture/bounded-contexts.md) yêu cầu.

### 2.3 Ranh giới module

**Tốt:** module thương mại chỉ vượt ranh giới qua `Contracts`/`Events`. Chỉ có hai ngoại lệ, và quét bảng chỉ thấy một lần truy cập chéo:

| Vi phạm | Chỗ | Test hiện tại có bắt? |
|---|---|---|
| Storefront dùng `Customer\Http` (middleware `AuthenticateCustomer`, `AddressRequest`, `PasswordRequest`, `ProfileRequest`) | `Storefront/Http/Middleware/RequireCheckoutSignIn.php`, `AccountSettingsController` | Không — test R5 của module downstream không cấm tầng `Http` |
| Pricing join bảng `variants` của Catalog (R11) | `Pricing/Application` | Không — R11 chỉ kiểm khi review |
| Catalog, Pricing, Inventory không nằm trong danh sách "consumer" của test R5 | `tests/Architecture/ArchitectureTest.php` | — |

**Phụ thuộc ngược thứ tự `config/modules.php`** ("upstream trước"):

| Cạnh ngược | Qua | Nguyên nhân |
|---|---|---|
| Ordering → Checkout | `Checkout\Contracts\ShippingAddresses` | Chuẩn hoá địa chỉ (danh mục địa giới) đặt ở Checkout nhưng Ordering (sửa địa chỉ đơn) và Customer (sổ địa chỉ) cũng cần |
| Customer → Checkout | `ShippingAddresses` | như trên |
| Promotion → Ordering | `OrderStatistics`, `SalesTotals` | `CampaignService` tổng hợp kết quả chiến dịch: đơn dùng khuyến mãi (`promotion_usages` của Promotion) + đơn dùng bảng giá của chiến dịch + doanh số — hai phần sau là dữ liệu của Ordering |
| Identity → Extension | `Extension\Application\Admin\AdminNavigation` | Vòng 0 tự vướng nhau (Extension cũng dùng `Identity\Application`) |
| Payment → Fulfillment | `Events\ShipmentStatusChanged` | Nghe event để ghi nhận thu COD — **hợp lệ** (R7 cho phép nghe event); giữ nguyên |

Ba vòng phụ thuộc hai chiều ở mức contract (Checkout↔Ordering, Checkout↔Customer, Ordering↔Promotion) đều bắt nguồn từ các cạnh ngược trên.

**Vòng 0 chưa có API khai báo:** module thương mại gọi thẳng class `Application` của vòng 0: `AdminNavigation` (12 nơi), `PermissionRegistry` (12), `ScopeOptions`, `AccountPages`, `PluginViews`. Mọi đổi trong các class này đều có thể làm vỡ module mà không snapshot nào báo.

### 2.4 Hợp đồng plugin

| Hạng mục | Hiện trạng |
|---|---|
| Version | Một số version cho cả Core lẫn hợp đồng plugin; Core tăng mỗi commit. Version hợp đồng tách riêng mới ở mức đề xuất ([plugin-system §6.1](../05-plugin/plugin-system.md)) |
| Hai loại người dùng `Contracts` | `Contracts/` vừa là API giữa module, vừa là API cho plugin. Đổi một contract chỉ module dùng vẫn bị tính là "đổi API plugin" |
| Mức ổn định | Chỉ hook có `experimental` (3 hook). Contract mới của EP v3 vào thẳng mức ổn định |
| Contract test thiếu (21 tag) | **EP v3:** `OrderRiskSignal`, `ConfirmationChannel`, `CarrierAssignment`, `PackingSlipTemplate`, `BulkPriceMode`, `PriceRounding`, `RefundPayout`, `BackorderPolicy` · **Plugin hệ thống:** `PhoneNumberPolicy`, `AddressDirectory` · **Còn lại:** `OrderStatusAction`, `CartLineOption`, `VideoDriver`, `ImageFormat`, `VariantUsage`, `PluginHealthCheck`, `ReportProvider`, `DashboardWidget`, `StockPageLink`, `AlertChannel`, `SitemapProvider` |
| Deprecation log `vani.deprecation` | Designed, chưa có code |
| Circuit breaker theo plugin | Designed ([plugin-system §8](../05-plugin/plugin-system.md)) |

## 3. Đề xuất theo bốn trụ

### 3.1 Module độc lập

| # | Việc | Kết quả |
|---|---|---|
| M1 | Arch test **thứ tự module**: module chỉ dùng `Contracts` của module đứng **trước** nó trong `config/modules.php`; `Events` được nghe từ mọi module | Cạnh ngược mới → CI đỏ |
| M2 | Đưa `AddressDirectory` + `ShippingAddresses` xuống **`Shared\Contracts`**, cùng chỗ với `PhoneNumberPolicy`: địa giới hành chính là chuẩn hoá dữ liệu trung lập, không phải nghiệp vụ checkout. Plugin `vani.provinces-vn` đổi `use` | Gỡ Ordering→Checkout và Customer→Checkout |
| M3 | Bỏ Promotion→Ordering: "Kết quả chiến dịch" (đơn, doanh số) là báo cáo, đứng sau cả Promotion lẫn Ordering → chuyển thành báo cáo của `vani.reports` (`ReportProvider`), đọc `Campaigns` + `OrderStatistics`. Promotion chỉ giữ cấu hình chiến dịch và số lượt dùng của chính nó | Gỡ vòng Ordering↔Promotion |
| M4 | Mở rộng test R5 cho **mọi** module, kể cả Catalog, Pricing, Inventory, và cấm thêm tầng `Http` của module khác. Storefront tự có middleware/FormRequest, hoặc Customer cung cấp qua contract (vd. `CustomerSessions` đã có) | Gỡ Storefront→`Customer\Http` |
| M5 | Arch test **sở hữu bảng (R11)**: bảng thuộc module nào (theo migration `Schema::create`) thì chỉ module đó dùng `DB::table`/`join`/rule `exists:`. Sửa Pricing join `variants` bằng `VariantDirectory` | R11 có test giữ, không còn chỉ dựa vào review |

### 3.2 Core nhỏ

| # | Việc | Kết quả |
|---|---|---|
| C1 | **API vòng 0 khai báo rõ**: chuyển `AdminNavigation` vào `Identity\Contracts` (mục menu gắn quyền), `PermissionRegistry`/`ScopeOptions` vào `Identity\Contracts`, `AccountPages`/`PluginViews` vào `Extension\Contracts`. Arch test: module vòng 1 chỉ dùng `Contracts` + `Shared\{Domain,Support,Context,Http}` của vòng 0 | Gỡ Identity→Extension; vòng 0 có bề mặt nằm trong snapshot |
| C2 | **Báo cáo kích thước Core theo đợt** (không chặn CI): số dòng theo module và tầng, số class public, số tag/hook. Ghi vào §2 của tài liệu này mỗi đợt | Thấy được Core phình ở đâu |
| C3 | **Rà theo tiêu chí vào Core** ([commerce-kernel §2](../02-architecture/commerce-kernel.md)) cho các phần sau. Mỗi phần cần Owner quyết định giữ hay chuyển plugin: `Catalog\Application\Media` (1k dòng, driver ảnh/video); `Storefront\Application\{Theme,Menus,Links,Blocks}` (1,5k dòng, gần vai trò của `vani.cms`); `Integration` (3,4k dòng, ERP đóng băng) — đề xuất **đóng băng tăng trưởng**, chỉ sửa lỗi | Danh sách quyết định, chưa chuyển code trong K-đợt này |

### 3.3 Domain rõ ràng

| # | Việc | Kết quả |
|---|---|---|
| D1 | **Payment**: tách khỏi `PaymentService` thành Domain thuần: máy trạng thái khoản thanh toán, quy tắc hoàn tiền (không vượt số đã thu, số còn hoàn được), chuyển trả (không chuyển hai lần), phân loại lệch đối soát. Service còn lại chỉ khoá dòng, gọi Domain, ghi sổ, phát event | `PaymentService` < 400 dòng; unit test Domain không cần DB |
| D2 | **Returns**: aggregate yêu cầu đổi trả (số lượng ≤ đã giao trừ đã trả, hạn đổi trả, tiền hoàn theo dòng) vào `Domain/` | Như trên |
| D3 | **Fulfillment**: máy trạng thái vận đơn + quy tắc thu COD vào `Domain/` (đã có một phần) | Như trên |
| D4 | **Controller mỏng**: truy vấn danh sách/chi tiết của `OrderController`, `ProductController`, `ShipmentController`, `ReturnController` chuyển sang class `Application/Queries` (đã có mẫu `CustomerQueries`, `StockGrid`). Thu hẹp arch test R9: controller không dùng `Persistence\Models` | 24 → 0 controller truy vấn trực tiếp |
| D5 | Phân loại domain trong [bounded-contexts §4](../02-architecture/bounded-contexts.md) đã sửa cho đúng code (2026-11-17: Checkout, Storefront là tầng điều phối; Payment/Returns/Fulfillment là mục tiêu rich domain). Còn lại: mỗi module có `README.md` ngắn — mức domain, bất biến, contract công bố, bảng sở hữu | Tài liệu khớp code (R24) |
| D6 | **Ngôn ngữ chung**: bổ sung [glossary](../00-overview/glossary.md) các từ mới của X1–X7: chờ xác nhận, chờ đóng gói, đẩy lên hãng, đặt trước, chờ hàng, hàng đang về, đối soát lệch, chuyển trả, ghi chú CSKH, tín hiệu rủi ro | Admin, code và tài liệu dùng cùng từ |

### 3.4 Plugin ổn định

| # | Việc | Kết quả |
|---|---|---|
| P1 | **ADR-048 — version API mở rộng tách khỏi Core** (đã đề xuất ở [plugin-system §6.1](../05-plugin/plugin-system.md)): hằng `vanishop.extension_api` (major.minor) chỉ tăng khi bề mặt plugin đổi; manifest khai `requires.extension_api`; `PublicApiSnapshotTest` bắt tăng hằng này khi chữ ký đổi. Đổi phá vỡ → tăng major. Version Core vẫn tăng theo commit như hiện nay | Plugin không vỡ âm thầm; Core thay đổi nội bộ không ép nâng plugin |
| P2 | **Tách API cho plugin khỏi API giữa module**: đánh dấu contract dành cho plugin (thuộc tính PHP `#[PluginApi]` hoặc danh sách trong snapshot). Snapshot chia hai phần; P1 chỉ tính phần plugin. Arch test R5: plugin chỉ dùng phần đã đánh dấu | Bề mặt plugin nhỏ lại, khoảng 130 class thay vì 291 |
| P3 | **Mức ổn định cho contract**: `stable` / `experimental`, giống hook. Điểm mở rộng mới vào ở `experimental` và lên `stable` sau một vòng phát hành có plugin thật dùng. EP v3 (X1–X7) bắt đầu ở `experimental` | Được sửa điểm mở rộng mới mà không phải tăng major |
| P4 | **Trả nợ R26: contract test cho 21 tag** (§2.4). Ưu tiên 8 tag EP v3 và 2 tag của plugin hệ thống. Plugin tham chiếu chạy bộ test đó | Mỗi tag public có contract suite |
| P5 | **Deprecation log `vani.deprecation`**: điểm `@deprecated` ghi log (một lần mỗi request/plugin) và hiện ở `vani:plugin:doctor` | Biết plugin nào sắp vỡ trước khi gỡ |
| P6 | **Circuit breaker theo plugin** cho slot/filter không giao dịch: quá N lỗi/phút → tạm bỏ qua plugin 5 phút + cảnh báo | Plugin hỏng không làm trắng trang Admin/storefront |
| P7 | **Ràng buộc version thật trong manifest**: plugin trong repo khai `requires.extension_api` đúng bản đang chạy; test chặn khai lỏng hơn major hiện tại | Hết tình trạng `^0.4` cho mọi plugin |

## 4. Kế hoạch theo đợt

Mỗi đợt một commit, chạy đủ bộ test, cập nhật §2 của tài liệu này và [CHANGELOG-extension](../04-extension/CHANGELOG-extension.md). Dev không cần tương thích ngược: đổi namespace, gỡ đường cũ, không alias.

| Đợt | Nội dung | Vì sao trước/sau | Kiểm chứng |
|---|---|---|---|
| **K1 — Ranh giới có test** ✅ 0.5.0 | M1, M4, M5 (arch test `ModuleBoundaryTest`) + sửa vi phạm hiện có: M2, M3, Pricing join `variants`, Storefront→`Customer\Http` | Rẻ, rủi ro thấp; khoá ranh giới trước khi tách Domain | Xong 2026-11-17: 3 vòng hai chiều biến mất; còn 1 cạnh ngược có ghi nợ (Identity→`AdminNavigation`, C1/K4); không còn truy cập bảng chéo |
| **K2 — Hợp đồng plugin** | P1 (ADR-048), P2, P3, P7 | Cần trước khi viết thêm plugin hoặc tăng điểm mở rộng | Đổi một contract plugin mà không tăng `extension_api` → đỏ |
| **K3 — Kiểm chứng plugin** | P4, P5, P6 | Dựa trên phân loại của K2 (chỉ viết suite cho contract plugin) | 44/44 tag có suite; breaker có test |
| **K4 — Vòng 0 và Domain** | C1, D1–D4 (Payment → Returns → Fulfillment → controller) | Thay đổi lớn nhất; làm khi ranh giới và hợp đồng đã có test giữ | `PaymentService` < 400 dòng; R9 chặt; Domain có unit test |
| **K5 — Tài liệu và quyết định** | C2, C3, D5, D6 | Chốt lại sau khi code ổn định | README mỗi module; danh sách quyết định C3 có câu trả lời |

## 5. Quyết định của Owner

Owner trả lời 2026-11-17: **đồng ý cả 7 đề xuất** dưới đây.

| # | Câu hỏi | Đề xuất (= quyết định) |
|---|---|---|
| 1 | Duyệt ADR-048: version API mở rộng tách khỏi version Core (P1)? | Đồng ý — khắc phục thực tế `^0.4` không bảo vệ plugin |
| 2 | Đánh dấu contract dành cho plugin (P2): thuộc tính PHP hay danh sách trong snapshot? | Thuộc tính `#[PluginApi]` — nằm ngay cạnh code, arch test đọc được |
| 3 | EP v3 bắt đầu ở mức `experimental` (P3)? | Đồng ý — các contract mới chưa có plugin bên ngoài repo dùng |
| 4 | `ShippingAddresses`/`AddressDirectory` xuống `Shared` (M2)? | Đồng ý — cùng mẫu `PhoneNumberPolicy` |
| 5 | "Kết quả chiến dịch" chuyển từ trang chiến dịch (Promotion) sang báo cáo của `vani.reports` (M3)? Trang chiến dịch giữ link sang báo cáo | Đồng ý — báo cáo đọc dữ liệu nhiều module là việc của plugin báo cáo |
| 6 | C3: giữ hay chuyển plugin `Catalog\Media`, `Storefront\{Theme,Menus,Links,Blocks}`; đóng băng tăng trưởng `Integration`? | Chỉ đóng băng `Integration` ngay; hai phần kia đánh giá ở K5 |
| 7 | Thứ tự K1 → K5 như §4? | Đồng ý; K4 có thể tách nhỏ theo module |

## 6. Không làm trong đợt này

- Không thêm tính năng hay điểm mở rộng mới (trừ việc chuyển chỗ ở M2, C1).
- Không tách service / không gọi HTTP giữa module (R19).
- Không làm plugin SDK cho bên thứ ba ([ADR-033 mục 5](../19-adr/ADR-033-out-of-scope.md)). "Plugin ổn định" ở đây là cho plugin nội bộ.
- Giữ nguyên các ràng buộc hiện hành: kết nối ngoài đóng băng, chỉ tiếng Việt nhưng giữ cơ chế i18n, tìm kiếm chỉ bằng SQL, SME trước.
