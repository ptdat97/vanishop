# Đặc tả đối chiếu hệ tham chiếu v3 → kế hoạch refactor `modules/`

> Trạng thái: **Designed** (đầu ra của vai trò nghiên cứu, 2026-11-03; **Owner đã trả lời §7 cùng ngày**, các mục bị ảnh hưởng đã sửa theo). Chưa có code nào được viết theo tài liệu này; trạng thái từng hạng mục cập nhật ở [status](../00-overview/status.md) khi làm.
>
> Liên quan: [reference-comparison](../02-architecture/reference-comparison.md) (kết luận học gì, giữ gì — tài liệu này **chi tiết hoá** nó theo từng module), [commerce-kernel](../02-architecture/commerce-kernel.md) (cái gì vào Core, cái gì là plugin), [ADR-033](../19-adr/ADR-033-out-of-scope.md) (phạm vi), [ADR-036](../19-adr/ADR-036-service-result-hooks.md) (hook tại kết quả service).

## 0. Đọc trước: clean-room

Tài liệu này được viết theo [clean-room §3](../01-principles/clean-room-license.md) ở **vai trò nghiên cứu**: người viết đã đọc mã nguồn hệ tham chiếu (bản v3 mới hơn bản đã nghiên cứu ngày 2026-10-02) và chỉ ghi lại **khái niệm, hành vi, quy tắc nghiệp vụ** bằng lời của VaniShop.

- Tài liệu **không** chứa tên hàm, tên class, tên bảng/cột, tên hook, tên route hay chuỗi hiển thị của hệ tham chiếu. Mọi tên trong tài liệu là tên **đề xuất của VaniShop**.
- Người code **chỉ đọc tài liệu này** và các tài liệu nó dẫn tới. Không mở mã nguồn hệ tham chiếu, không đưa nó vào context của AI agent khi sinh code (clean-room §3.4). Phiên làm việc đã đọc mã nguồn tham chiếu **không** dùng để viết code VaniShop.
- ⚠️ **Việc cần làm ngay**: thư mục mã nguồn hệ tham chiếu v3 cùng file nén của nó đang nằm cạnh repo VaniShop trên máy dev (thư mục cha của `vanishop/`). Theo clean-room §7, sau khi Owner duyệt tài liệu này, chuyển chúng ra lưu trữ offline (chỉ phục vụ audit), không để trong thư mục làm việc của agent.

## 1. Tóm tắt

**Kiểm kê hệ tham chiếu v3** (đếm file PHP theo thư mục lõi): khu quản trị web ~150, API quản trị ~30, storefront web ~90, API storefront ~20, model 58, repository 31, service dùng chung 16, **AI ~170** (mới so với bản đã nghiên cứu), bộ cài ~25, lệnh console 16, mail + notification 28, thư viện tiện ích 8, định nghĩa trường 24, hook 5, plugin loader 3. Cộng với một file **hàm toàn cục ~60 hàm**.

**Nhận định**

1. **Lõi thương mại**: VaniShop đã vượt (tồn kho reservation, 4 chiều trạng thái đơn, tiền số nguyên, idempotency, outbox). Không refactor lõi theo hệ tham chiếu; chỉ bổ sung vài quy tắc nghiệp vụ nhỏ (§3).
2. **Khoảng trống thật nằm ở bề mặt**: điều hướng/link có kiểu + trình sửa menu, khối trang chủ, cấu hình vận hành storefront (bảo trì, ẩn giá, khách vãng lai), tài khoản khách (yêu thích, xoá tài khoản, đã xem), quản trị nhân viên/vai trò, cấu hình bộ lọc, Admin API.
3. **Hàm toàn cục**: VaniShop chỉ có helper hook. Hệ tham chiếu dùng helper toàn cục làm "API ngắn" cho theme và plugin (đọc cấu hình, sinh URL, định dạng tiền, ảnh thu nhỏ, người dùng hiện tại). Để chuyển plugin dễ ([ADR-034](../19-adr/ADR-034-vanicommerce-plugin-migration.md)), **một bộ helper mỏng tên `vani_*`** bọc contract sẵn có — Owner đã duyệt: [ADR-037](../19-adr/ADR-037-vani-helpers.md) (§4).
4. **AI & agent**: hệ tham chiếu v3 thêm trợ lý AI trong Admin (nhiều agent, công cụ thao tác dữ liệu, bộ nhớ hội thoại, xác nhận trước khi ghi, hạn mức, chống prompt injection, nhật ký) và một máy chủ MCP + giao thức khám phá catalog cho agent bên ngoài. **Owner đóng băng (2026-11-03)** — §3.18 chỉ giữ lại phần ghi nhận.

## 2. Bản đồ: hệ tham chiếu → module VaniShop

| Khu vực hệ tham chiếu (khái niệm) | Module / nơi đích ở VaniShop | Ghi chú |
|---|---|---|
| Cấu hình hệ thống nạp một lần vào bộ nhớ, đọc qua helper | `Tenancy` (`Settings`) | Đã có; thiếu nhóm khoá vận hành storefront (§3.2) |
| Đa ngôn ngữ, đa tiền tệ, tỷ giá | `Shared` (`StoreLocale`, `Money`) | Tiền chỉ VND (§5); ngôn ngữ vi/en đã có |
| Quốc gia, châu lục, vùng, tỉnh | `vani.provinces-vn` + `Checkout` (`AddressDirectory`) | Chỉ địa giới VN |
| Nhân viên, vai trò, quyền, token API quản trị | `Identity` | Thiếu Admin quản lý nhân viên/vai trò (§3.3) |
| Hook (filter/action/blade), plugin loader, trường cấu hình plugin | `Extension` | Đã có và chặt hơn; thiếu helper ngắn (§4) |
| Sản phẩm, SKU, thuộc tính, danh mục, thương hiệu, liên kết sản phẩm, lượt xem | `Catalog` | Thiếu ghi nhận lượt xem/"đã xem" (§3.5) |
| Giá theo nhóm khách (hệ số giảm toàn đơn) | `Pricing` (giá thành viên) | Khác chủ đích: giá theo dòng, không giảm toàn đơn (§3.6) |
| Giỏ hàng, chọn dòng để thanh toán, mua ngay, giỏ mini | `Cart` | **Implemented** (Core 0.3.64, §3.8) |
| Checkout, tổng tiền theo pipeline, thuế theo vùng, khối lượng | `Checkout` | Đã có pipeline; thuế VN là plugin |
| Máy trạng thái đơn, lịch sử, vận đơn, thanh toán, tự huỷ/tự hoàn tất | `Ordering`, `Fulfillment`, `Payment` | Đã có, tách 4 chiều |
| Đổi trả + lý do | `Returns` | Đã có; thiếu danh mục lý do do Admin quản lý (§3.13) |
| Khách hàng, nhóm, địa chỉ, yêu thích, quên mật khẩu, mã xác minh | `Customer` | Thiếu yêu thích (plugin), xoá tài khoản tự phục vụ (§3.15) |
| Mail theo sự kiện + nhật ký mail, push app | `Notification` | Đã có; push app là kênh plugin |
| Trang + chuyên mục bài viết, sản phẩm gắn bài viết | plugin `vani.cms` | Thiếu chuyên mục, gắn sản phẩm (§3.17) |
| Trang chủ kéo-thả, header, footer, menu, trang chủ app | `Storefront` (page builder) + Extension | Thiếu link có kiểu, menu editor, header/footer (§3.16) |
| Bộ lọc nhiều chiều cấu hình được | `Catalog` (`ProductFilters`) + Storefront | Thiếu cấu hình facet trong Admin (§3.5) |
| Sitemap | `Storefront` (`SitemapProvider`) | Đã có |
| Báo cáo, dashboard | `Extension` (`ReportProvider`, `DashboardWidget`) + `vani.reports` | Đã có |
| Quản lý file | Thư viện media Core | Đã có |
| Dịch máy nội dung | `TranslationProvider` (Later) | §3.4 |
| API storefront | `/api/storefront/v1` | Đã có phần lớn |
| API quản trị | `/api/admin/v1` (Designed) | §3.19 |
| AI trợ lý, MCP, khám phá catalog cho agent | — | ❄️ Đóng băng (§3.18) |
| Bộ cài web, kho plugin trực tuyến, kiểm tra license theo domain, chuyển dữ liệu từ nền tảng khác | — | Không áp dụng (§5) |

## 3. Đặc tả theo module

Mỗi mục: **Tham chiếu** (hành vi đã quan sát, viết bằng lời) · **VaniShop hiện có** · **Đặc tả cần làm** · **Vị trí** (Core/plugin, theo [commerce-kernel](../02-architecture/commerce-kernel.md)) · **Ưu tiên** (P0 trước go-live, P1 sau go-live, P2 khi có nhu cầu).

### 3.1 Shared

**Tham chiếu**: định dạng tiền theo tiền tệ hiện hành (có hoặc không ký hiệu), quy đổi tiền tệ theo tỷ giá, định dạng ngày giờ một mẫu cố định, rút gọn số lớn (nghìn/triệu/tỷ) cho thống kê, cắt chuỗi theo ký tự đa byte, đơn vị khối lượng và quy đổi giữa chúng, "registry" giá trị theo request (ghi đè locale/tiền tệ/người dùng trong một request), lấy IP client, phát hiện thiết bị di động, phát hiện HTTPS sau proxy.

**VaniShop hiện có**: `Money` + `MoneyFormatter`, `StoreClock`, `StoreLocale`, `CurrentContext`/`ContextScope` (actor + locale theo request), correlation id.

**Đặc tả cần làm**
1. **Định dạng số rút gọn** cho dashboard: `1 250` → `1,3 N`, `2 400 000` → `2,4 Tr`, `1 200 000 000` → `1,2 Tỷ`; dấu thập phân theo locale; dùng trong `DashboardWidget`. Thuộc `Shared\Formats` (đã có `Formats` trong Extension — kiểm tra trước, mở rộng thay vì tạo mới).
2. **Khối lượng**: một kiểu giá trị `Weight` (gram, số nguyên) + quy đổi từ kg/g/lb/oz khi nhập liệu. Lưu gram nguyên để tránh số thực trong tính phí ship. Kiểm tra cách Catalog/`vani.ghn` đang lưu khối lượng trước khi đổi.
3. ✅ (R1-1, 2026-11-03) **IP client**: không đọc header của CDN/proxy trực tiếp (giả mạo được). Chỉ dùng IP Laravel trả về sau khi cấu hình proxy tin cậy; ghi rõ trong [security](../15-security/security.md).
4. ✅ (R1-1, 2026-11-03) **Host an toàn**: URL tuyệt đối (email, sitemap, callback cổng thanh toán) chỉ sinh từ `APP_URL`, không từ header `Host` của request. Thêm test cho link trong email khi request có `Host` lạ.
5. **Không làm**: registry toàn cục theo request — VaniShop dùng `ContextScope` (có phạm vi, tự khôi phục), không có kho giá trị toàn cục ghi đè được.

**Vị trí**: Core `Shared`. **Ưu tiên**: P1 (1, 2), P0 (3, 4 — bảo mật).

### 3.2 Tenancy — cấu hình cửa hàng

**Tham chiếu**: toàn bộ cấu hình nằm trong một bảng nhóm/khoá/giá trị, nạp một lần mỗi request vào config; có một điểm lọc khi đọc cấu hình hệ thống (plugin ghi đè giá trị đọc ra). Các nhóm khoá quan sát được: thông tin cửa hàng (tên, địa chỉ, quốc gia/tỉnh mặc định, meta SEO), giao diện đang dùng, số sản phẩm mỗi trang, ảnh placeholder, kích thước ảnh gốc sản phẩm, URL CDN, từ khoá tìm kiếm nổi bật, **chế độ bảo trì**, **chỉ hiện giá khi đã đăng nhập**, **cho phép khách vãng lai đặt hàng**, **duyệt khách đăng ký**, captcha khi đăng ký, nhóm khách mặc định, số phút tính lại một lượt xem sản phẩm, **tự huỷ đơn chưa thanh toán sau N phút**, **tự hoàn tất đơn đã giao sau N ngày**, danh sách hãng vận chuyển, bắt buộc SĐT trong địa chỉ, cấu hình mail (driver, SMTP, gửi qua queue), địa chỉ nhận mail cảnh báo, đường dẫn Admin, cấu hình header/footer/menu/trang chủ (JSON), cấu hình bộ lọc, cấu hình AI.

**VaniShop hiện có**: `Settings` (`get`/`set`/`forget`/`explicit`, secret mã hoá, `SettingDefinition` sinh form Admin), `config/vanishop.php` cho múi giờ/ngôn ngữ/tiền/rate limit; hết hạn thanh toán và hoàn tất sau hạn đổi trả đã có trong Payment/Fulfillment.

**Đặc tả cần làm** — thêm nhóm định nghĩa cấu hình **`storefront.*`** (khai báo bằng `SettingDefinition`, có rule validate, hiển thị trong Admin → Cấu hình):

| Khoá đề xuất | Kiểu | Mặc định | Hành vi |
|---|---|---|---|
| `storefront.maintenance` | bool | false | Bật: mọi trang storefront trả 503 + trang bảo trì của theme, header `Retry-After`; nhân viên đã đăng nhập Admin vẫn xem được; `/health`, IPN thanh toán, webhook vận chuyển, Integration API **không** bị chặn |
| `storefront.maintenance_message` | text | rỗng | Nội dung trang bảo trì |
| `storefront.price_visibility` | enum `public`/`members` | `public` | `members`: khách chưa đăng nhập thấy "Đăng nhập để xem giá", không thêm được vào giỏ; Storefront API trả giá `null` + cờ; JSON-LD bỏ `offers` |
| `storefront.guest_checkout` | bool | true | Tắt: checkout yêu cầu đăng nhập (OTP); API trả lỗi nghiệp vụ có mã |
| `storefront.per_page` | int 12–60 | 24 | Số sản phẩm mỗi trang danh mục/tìm kiếm |
| `storefront.popular_searches` | danh sách chuỗi | rỗng | Gợi ý dưới ô tìm kiếm khi chưa gõ |
| `storefront.placeholder_image` | media id | ảnh của theme | Ảnh thay thế khi sản phẩm thiếu ảnh |

Ghi chú cho `storefront.price_visibility` (Owner cần, 2026-11-03):
- Trang cache CDN không được chứa giá khi bật `members`: giá trên thẻ sản phẩm/PDP được điền sau qua endpoint giá đã có (`/_vani/gia`) chỉ khi có phiên khách đã đăng nhập; HTML cache luôn là bản "Đăng nhập để xem giá".
- Giỏ, checkout, Storefront API (`/products`, `/products/{slug}`, giỏ) kiểm tra cùng một chính sách (`PriceVisibility` trong Storefront, một chỗ quyết định) — không chỉ ẩn ở view.
- Sitemap, feed và JSON-LD không chứa giá khi bật `members`.
- Đổi cấu hình phải xoá cache CDN của trang storefront (ghi trong [operations](../18-operations/operations.md)).

Không có cấu hình duyệt tài khoản: Owner quyết định tài khoản mới **không cần duyệt** (2026-11-03).

Quy tắc: plugin **không** ghi đè giá trị đọc ra (khác tham chiếu); muốn đổi hành vi thì dùng hook service ([ADR-036](../19-adr/ADR-036-service-result-hooks.md)) tại nơi dùng giá trị.

**Vị trí**: định nghĩa khoá thuộc module dùng nó (Storefront, Customer), lưu qua `Settings` của Tenancy. **Ưu tiên**: P0 (bảo trì, khách vãng lai, ẩn giá), P1 (còn lại).

**Trạng thái**: ✅ `storefront.maintenance`, `storefront.maintenance_message` (Storefront) và `storefront.guest_checkout` (khoá định nghĩa ở Checkout — nơi chặn đặt hàng) — R1-2, 2026-11-03, Core 0.3.49, [storefront §5](../14-storefront/storefront.md). Nhân viên xem trước bằng cookie có chữ ký phát từ Admin (cookie phiên Admin giới hạn path nên storefront không đọc được). ✅ `storefront.price_visibility` — R1-3, 2026-11-03, Core 0.3.50 (`PriceVisibility` trong Storefront; giỏ/checkout qua middleware `vani.prices-visible`; khách đăng nhập nhận giá trên trang cache qua `/_vani/gia?p[]=`). Còn: các khoá P1.

### 3.3 Identity — nhân viên, vai trò

**Tham chiếu**: CRUD nhân viên + vai trò + gán quyền trong Admin; "root" là danh sách id nhân viên không bị giới hạn quyền; lệnh console tạo root đầu tiên và đổi mật khẩu root; nhân viên tự sửa hồ sơ, đổi ngôn ngữ giao diện Admin theo từng người; quên mật khẩu Admin qua email; token API cho nhân viên (Admin API); quyền chi tiết cho từng nhóm màn hình.

**VaniShop hiện có**: guard `staff`, vai trò + permission registry, scope owner/location, `Authorizer`, audit, `vani:staff:create-owner`, chính sách mật khẩu.

**Đặc tả cần làm**
1. **Admin → Nhân viên**: danh sách (tìm theo tên/email, lọc vai trò, trạng thái), tạo (gửi link đặt mật khẩu một lần, hết hạn 24h — không đặt mật khẩu hộ), khoá/mở khoá, gán vai trò theo scope. Không xoá cứng nhân viên đã có audit (chỉ khoá). Không tự khoá hoặc tự hạ quyền chính mình; không khoá Owner cuối cùng.
2. **Admin → Vai trò**: tạo/sửa/xoá vai trò, chọn quyền theo nhóm (lấy từ permission registry, gồm quyền do plugin khai báo). Chặn xoá vai trò đang được gán.
3. **Hồ sơ của tôi**: đổi tên, mật khẩu (nhập mật khẩu cũ), ngôn ngữ Admin riêng (vi/en, lưu theo nhân viên, `StoreLocale` của Admin đọc giá trị này).
4. **Quên mật khẩu Admin**: link một lần qua email, rate limit theo email + IP, không tiết lộ email có tồn tại hay không; audit.
5. **Lệnh console**: `vani:staff:reset-password {email}` (in link đặt lại, không in mật khẩu).
6. Token Admin API: thiết kế cùng §3.19.

Mọi thao tác ghi audit qua `AuditLogger`. **Vị trí**: Core `Identity`. **Ưu tiên**: P0 (1, 2, 4), P1 (3, 5).

**Trạng thái**: ✅ 1, 2, 4 — R1-4, 2026-11-03, Core 0.3.51 ([security §2](../15-security/security.md)). Bổ sung so với đặc tả: không cấp quyền mình không có (chống leo thang quyền); mở khoá nhân viên chưa từng đăng nhập → quay về `invited` + gửi lời mời mới. Còn: 3, 5 (R3), 6 (§3.19).

### 3.4 Extension — hook, plugin, cấu hình plugin

**Tham chiếu**
- Ba loại điểm mở rộng: lọc giá trị (filter), hành động (action), và **chèn/bọc HTML trong view** (một khối có thể được nối thêm, thay thế, hoặc bọc nội dung gốc; có ưu tiên; có cơ chế "giả lập" kết quả khi debug).
- Plugin = thư mục tự chứa: manifest (mã, tên đa ngôn ngữ, loại, phiên bản, tác giả), lớp khởi động đăng ký hook, khai báo trường cấu hình (kiểu, nhãn đa ngôn ngữ, bắt buộc, rule validate, giá trị chọn), route storefront/Admin, view, ngôn ngữ, migration, thư mục tài nguyên tĩnh phục vụ qua một route chung.
- Loại plugin: thanh toán, vận chuyển, tổng tiền, dịch máy, giao diện, khác. Plugin bật theo thứ tự ưu tiên; plugin loại "tổng tiền" thêm dòng vào tổng đơn.
- Plugin tải ảnh thu nhỏ từ thư mục riêng của nó.
- Kiểm tra cập nhật phiên bản plugin từ máy chủ của nhà phát hành.

**VaniShop hiện có**: `Hook` (filter/action/collect/slot, registry, strict mode, đo thời gian), manifest `vanishop.json`, dependency resolver, lifecycle, `adminFormSection`/`adminColumn`/…, `storefrontRoutes`/`storefrontPages`, slot storefront, hook service `vani.service.*`, helper `hook_filter`/`hook_action`/`add_hook_filter`/`add_hook_action`/`vani_*`.

**Đặc tả cần làm**
1. **Helper ngắn cho plugin và theme** — §4.
2. **Tài nguyên tĩnh của plugin**: plugin khai báo thư mục `public` trong manifest; khi bật plugin, Core **publish** sang `public/plugins/<id>/` với tên file có hash (cache dài hạn), không phục vụ qua route PHP. Helper `vani_asset()` trả URL. Gỡ plugin xoá thư mục publish.
3. **Ảnh của plugin**: plugin muốn ảnh thu nhỏ thì đăng ký ảnh vào Thư viện media Core (đã có khử trùng lặp), không tự resize.
4. **Không làm**: bọc/thay HTML của view (giữ quyết định [ADR-025](../19-adr/ADR-025-native-storefront-ssr-slots.md): slot chỉ nối thêm, thay khối bằng override view trong theme); kiểm tra cập nhật từ máy chủ ngoài ([ADR-026](../19-adr/ADR-026-plugin-deploy-via-code.md)).
5. **`TranslationProvider`** (Later → P2): plugin dịch máy đăng ký nhà cung cấp; form bản dịch Admin hiện nút "Dịch từ tiếng Việt" cho từng trường khi có ít nhất một provider bật. Kết quả chỉ điền vào form, nhân viên lưu bằng tay.

**Vị trí**: Core `Extension`. **Ưu tiên**: P1 (1, 2), P2 (3, 5).

### 3.5 Catalog

**Tham chiếu**: sản phẩm có biến thể sinh từ các chiều (màu, size…), mỗi biến thể một SKU (mã, giá, giá gốc, giá vốn, tồn, ảnh); sản phẩm có video, khối lượng + đơn vị, cờ cần giao hàng, lớp thuế, vị trí sắp xếp, bật/tắt, xoá mềm; thuộc tính theo nhóm; sản phẩm liên quan; đếm lượt xem có chống đếm trùng trong N phút; tìm kiếm + gợi ý (sản phẩm, danh mục, thương hiệu); "sản phẩm nổi bật"; **bộ lọc nhiều chiều** do Admin cấu hình (khoảng giá + chọn thuộc tính nào làm bộ lọc); danh sách thương hiệu theo chữ cái đầu; dữ liệu mẫu sinh tự động cho dev.

**VaniShop hiện có**: Style/Style Color/variant, danh mục cây, thương hiệu, thuộc tính spec/internal, màu + size theo hệ size, media, bộ sưu tập, liên kết + video sản phẩm, `SearchProvider` (database + Meilisearch), `ProductFilters` (facet màu, thuộc tính, thương hiệu), `vani.demo-catalog`.

**Đặc tả cần làm**
1. **Cấu hình facet** (Admin → Catalog → Bộ lọc): bật/tắt từng facet (giá, màu, size, thương hiệu, từng thuộc tính `spec`), thứ tự hiển thị, kiểu hiển thị (danh sách tick, ô màu, thanh trượt giá). Lưu trong `Settings` (`catalog.facets`). `ProductFilters` và trang danh mục đọc cấu hình này; facet không có giá trị nào trong tập kết quả thì ẩn.
2. **Lượt xem & "đã xem gần đây"**: event `ProductViewed` (style id, customer id nếu có, phiên ẩn danh) phát từ PDP **qua beacon JS sau khi trang tải** (trang PDP cache CDN được, không ghi DB lúc render). Đếm lượt xem gom theo giờ (bảng tổng hợp, không ghi mỗi lượt một dòng); chống đếm trùng cùng phiên trong 30 phút. "Đã xem gần đây" lưu phía trình duyệt (tối đa 12 style id), render qua `GET /products?ids=` (đã có). Không lưu lịch sử xem phía server cho khách (giảm dữ liệu cá nhân).
3. **Thương hiệu theo chữ cái**: trang `/thuong-hieu` nhóm theo chữ cái đầu (bỏ dấu tiếng Việt khi nhóm: "Đ" vào nhóm riêng "Đ").
4. **Gợi ý tìm kiếm** trả cả danh mục và thương hiệu khớp tên (tối đa 3 mỗi loại) bên cạnh sản phẩm — kiểm tra `SearchProvider` hiện trả gì trước.
5. **Đã có, không làm lại**: biến thể, khối lượng (xem §3.1-2), xoá mềm (VaniShop dùng `archived`), dữ liệu mẫu.

**Vị trí**: Core `Catalog` + `Storefront`. **Ưu tiên**: P1 (1, 4), P2 (2, 3).

### 3.6 Pricing

**Tham chiếu**: nhóm khách có **hệ số giảm theo phần trăm trên tạm tính toàn đơn**, thêm thành một dòng giảm trong tổng đơn; có điểm lọc để plugin đổi hệ số.

**VaniShop hiện có**: bảng giá `member` theo nhóm khách (0.3.35) — giá theo dòng, priority, khung giờ.

**Đặc tả**: **giữ khác biệt chủ đích**. Giảm theo nhóm khách là giá theo dòng (hiển thị đúng trên thẻ sản phẩm, PDP, giỏ), không phải dòng giảm cuối đơn. Nếu Owner cần "giảm X% toàn đơn cho nhóm VIP" thì làm bằng khuyến mãi tự động có rule "thuộc nhóm khách" (plugin `vani.promotion-rules` thêm rule `in_customer_groups`), để đi qua giá sàn, ngân sách, truy vết `order_adjustments`.

**Vị trí**: plugin `vani.promotion-rules`. **Ưu tiên**: P2.

### 3.7 Inventory

**Tham chiếu**: tồn nằm trên SKU, trừ khi đơn chuyển sang đã thanh toán, cộng lại khi hoàn; kiểm tra tồn lúc thêm giỏ và lúc đặt.

**VaniShop**: đã vượt (reservation đa kho, ATS, ledger). **Không có việc refactor.**

### 3.8 Cart

**Tham chiếu**: mỗi dòng giỏ có cờ "đã chọn"; checkout chỉ lấy dòng đã chọn, dòng không chọn ở lại giỏ sau khi đặt; **mua ngay** = thêm dòng rồi chỉ chọn riêng dòng đó; chọn/bỏ chọn nhiều dòng một lần; giỏ mini (số lượng + vài dòng gần nhất) cho header; giới hạn số lượng mỗi dòng.

**VaniShop hiện có**: `Carts` (thêm cộng dồn, sửa, xoá, gộp), cờ dòng `unavailable/insufficient_stock/price_changed`, sự kiện trình duyệt `vani:cart-updated`.

**Đặc tả cần làm**
1. **Chọn dòng để thanh toán**: thêm thuộc tính `selected` (mặc định true) trên dòng giỏ; thao tác `select(lineIds)`, `deselect(lineIds)`, `selectOnly(lineIds)`. `Checkout::quote`/`placeOrder` chỉ dùng dòng đã chọn; sau khi đặt thành công, chỉ dòng đã chọn chuyển sang đơn, giỏ giữ dòng còn lại (giỏ **không** chuyển `converted` nếu còn dòng). Khuyến mãi tính trên dòng đã chọn. Dòng `unavailable` không chọn được.
2. **Mua ngay**: `POST /gio-hang` với cờ `buy_now` → thêm dòng, `selectOnly` dòng đó, chuyển thẳng tới checkout. Không xoá dòng khác.
3. **Giỏ mini**: endpoint đọc nhẹ (tổng số lượng + 3 dòng gần nhất + tạm tính), không tính khuyến mãi; dùng cho header qua `/_vani/phien` (đã có cơ chế phiên tách khỏi trang cache).
4. Concurrency: thao tác chọn dùng cùng khoá giỏ hiện có.

**Vị trí**: Core `Cart` + `Checkout` + `Storefront`. **Ưu tiên**: P1. **Test**: feature (đặt đơn giữ dòng chưa chọn), invariant (đơn chỉ có dòng đã chọn), API schema.

**Trạng thái**: **Implemented** (Core 0.3.64) — `cart_lines.selected`, `Carts::buyNow/select/deselect/selectOnly/markOrdered`, `PUT /carts/{id}/selection`, `POST /gio-hang/chon`, nút "Mua ngay", giỏ mini qua `/_vani/phien`. Sau khi đặt, dòng chưa chọn giữ nguyên trạng thái bỏ chọn. Test: `CartSelectionTest`, `tests/Feature/Invariants/CartSelectionInvariantsTest.php`.

### 3.9 Promotion

**Tham chiếu**: không có engine khuyến mãi trong lõi (do plugin). **Không có việc refactor**; xem §3.6 cho rule nhóm khách.

### 3.10 Checkout

**Tham chiếu**: checkout là một trang trạng thái: chọn/nhập địa chỉ giao và địa chỉ thanh toán (khách vãng lai nhập trực tiếp), chọn phương thức giao (phí tính theo khối lượng giỏ), chọn phương thức thanh toán, mỗi lần đổi thì tính lại và trả dữ liệu mới; tổng đơn là chuỗi các bộ tính (tạm tính, phí giao, thuế, giảm theo nhóm khách, dòng do plugin thêm) theo thứ tự; thuế theo **lớp thuế × địa chỉ** (giao/thanh toán/cửa hàng) với nhiều mức thuế và loại phần trăm/cố định; nếu không có sản phẩm cần giao thì ẩn bước giao hàng; trang thành công.

**VaniShop hiện có**: `Checkout::quote`/`placeOrder` stateless ([ADR-023](../19-adr/ADR-023-stateless-checkout.md)), `TotalsCalculator` theo priority, `TaxCalculator` (VAT VN là plugin), `ShippingRateProvider`, `AddressDirectory`.

**Đặc tả cần làm**
1. **Sản phẩm không cần giao**: **không làm** — Owner chỉ bán hàng vật lý (2026-11-03). Mọi đơn đều có giao hàng; không thêm cờ "không cần giao".
2. **Khối lượng giỏ** truyền vào `ShippingRateProvider` (gram, tổng theo dòng đã chọn) — kiểm tra context hiện có trước.
3. **Thuế theo vùng**: **không làm** trong Core (một cửa hàng VN; VAT là plugin `vani.tax-vn-vat`).

**Vị trí**: Core `Checkout`. **Ưu tiên**: P1 (2).

### 3.11 Ordering

**Tham chiếu**: một máy trạng thái (tạo → chờ thanh toán → đã thanh toán → đã giao → hoàn tất; huỷ; đang hoàn tiền), mỗi chuyển trạng thái kèm danh sách việc (ghi lịch sử, cộng số đã bán, trừ/hoàn tồn, tạo vận đơn, gửi thông báo, push app); plugin sửa được bảng chuyển; khách tự huỷ đơn chưa thanh toán, tự xác nhận "đã nhận hàng"; trả lại link thanh toán cho đơn chưa thanh toán; tự huỷ/tự hoàn tất theo lịch; số đã bán trên sản phẩm.

**VaniShop hiện có**: 4 chiều trạng thái, `OrderTransitions` (bảng chuyển không mở rộng), huỷ, huỷ một phần, `CustomerOrders`, hết hạn thanh toán, hoàn tất sau hạn đổi trả, `OrderStatistics::topStyles`.

**Đặc tả cần làm**
1. **Khách xác nhận đã nhận hàng**: trên trang đơn (native + API), khi vận đơn ở trạng thái đã giao hoặc đang giao quá N ngày, khách bấm "Đã nhận hàng" → ghi `order_events` nguồn `customer`, cho phép đơn hoàn tất sớm hơn hạn tự động **chỉ khi** hết hạn đổi trả hoặc khách xác nhận không đổi trả (tuỳ cấu hình `ordering.complete_on_customer_confirm`, mặc định false). Không đổi bảng chuyển.
2. **Thanh toán lại**: đơn online chưa thanh toán còn hạn → nút "Thanh toán" tạo lại `PaymentInitiation` từ cổng đã chọn (hoặc cho đổi cổng nếu cổng cũ không khả dụng). Kiểm tra Payment đã hỗ trợ khởi tạo lại chưa.
3. **Không làm**: plugin sửa bảng chuyển (lý do ở [reference-comparison §3](../02-architecture/reference-comparison.md)).

**Vị trí**: Core `Ordering` + `Payment` + `Storefront`. **Ưu tiên**: P1 (2), P2 (1).

### 3.12 Fulfillment

**Tham chiếu**: vận đơn là một bản ghi (hãng, mã vận đơn) gắn đơn lúc chuyển "đã giao"; danh sách hãng là cấu hình text.

**VaniShop**: đã vượt (`ShippingCarrier`, webhook, trạng thái chuẩn hoá). **Không có việc refactor.**

### 3.13 Returns

**Tham chiếu**: yêu cầu đổi trả theo từng dòng đơn (số lượng, loại: trả/đổi/sửa, lý do chọn từ danh mục, mô tả, ảnh), lịch sử trạng thái, khách đánh dấu "đã gửi hàng về"; Admin quản lý danh mục lý do đa ngôn ngữ; thông báo cho khách và Admin ở mỗi bước.

**VaniShop hiện có**: Returns đầy đủ vòng đời, đổi hàng, khách gửi/huỷ yêu cầu, `ReturnPolicy`.

**Đặc tả cần làm**
1. **Danh mục lý do đổi trả** do Admin quản lý (tên vi/en, thứ tự, bật/tắt, cờ "lỗi do cửa hàng" → dùng cho quy tắc phí ship trả hàng và báo cáo). Lý do đã dùng không xoá được, chỉ tắt. Kiểm tra hiện lý do đang là enum hay text.
2. **Khách báo "đã gửi hàng về"** kèm mã vận đơn trả (tuỳ chọn) → chuyển yêu cầu sang trạng thái đang gửi về (đã có trạng thái).
3. **Ảnh đính kèm** yêu cầu (tối đa 5, qua Thư viện media, chỉ khách sở hữu đơn và nhân viên xem được) — P2.

**Vị trí**: Core `Returns`. **Ưu tiên**: P1 (1, 2), P2 (3).

### 3.14 Payment

**Tham chiếu**: phương thức thanh toán = plugin loại thanh toán; bản ghi thanh toán theo đơn (mã giao dịch, số tiền, phản hồi thô); trang thanh toán do plugin render.

**VaniShop**: đã vượt. **Không có việc refactor** ngoài §3.11-2.

### 3.15 Customer

**Tham chiếu**: đăng ký (email + mật khẩu, captcha tuỳ chọn, chờ duyệt tuỳ chọn), đăng nhập, quên mật khẩu bằng mã xác minh gửi email, sửa hồ sơ, đổi mật khẩu, địa chỉ, **danh sách yêu thích**, **xoá tài khoản** tự phục vụ, nhóm khách mặc định, đăng nhập qua token cho app.

**VaniShop hiện có**: định danh SĐT + OTP, profile, địa chỉ, consent, token Bearer, merge, ẩn danh hoá, `AuthProvider`, nhóm khách + tag, `/tai-khoan/*` native.

**Đặc tả cần làm**
1. ✅ (R1-5, 2026-11-04, Core 0.3.57) **Xoá tài khoản tự phục vụ**: `/tai-khoan/xoa` — xác nhận bằng OTP; nếu còn đơn chưa hoàn tất/đổi trả đang mở thì từ chối (lỗi có mã, liệt kê đơn); nếu được → gọi luồng ẩn danh hoá sẵn có (`CustomerAnonymized`), thu hồi mọi token, đăng xuất. Ghi consent event. API tương ứng `DELETE /me`.
2. **Duyệt tài khoản**: **không làm** — Owner quyết định tài khoản mới không cần duyệt (2026-11-03).
3. **Yêu thích** → plugin `vani.wishlist` (đã có trong [plugin-catalog](../05-plugin/plugin-catalog.md)): thêm/bỏ từ thẻ sản phẩm và PDP (slot), trang `/tai-khoan/yeu-thich`, khách vãng lai lưu ở trình duyệt và gộp vào tài khoản khi đăng nhập; thông báo tuỳ chọn khi giảm giá/có hàng lại (consent marketing). Dữ liệu `plg_wishlist_*`.
4. **Captcha đăng ký/OTP**: không làm captcha hình; giữ rate limit theo SĐT/IP hiện có. Nếu bị spam OTP, thêm `ChallengeProvider` (Turnstile…) — P2, cần ADR.

**Vị trí**: Core `Customer` (1), plugin (3). **Ưu tiên**: P0 (1 — yêu cầu Nghị định 13/2023 về quyền xoá dữ liệu), P1 (3), P2 (4).

### 3.16 Storefront — điều hướng, link có kiểu, khối giao diện

**Tham chiếu**
- **Link có kiểu**: một link lưu dạng (loại, giá trị, nhãn đa ngôn ngữ tuỳ chọn); loại gồm danh mục, sản phẩm, thương hiệu, trang nội dung, chuyên mục bài viết, đơn hàng, đổi trả, trang tĩnh (route có tên), URL tuỳ ý. Khi render, link được phân giải thành URL + nhãn (nhãn mặc định lấy tên của đối tượng theo ngôn ngữ hiện hành); kết quả phân giải được cache.
- **Header/footer/menu**: Admin chỉnh menu nhiều cấp (mục có link có kiểu, nhãn, huy hiệu, cột nhóm, ảnh), footer (cột link, thông tin liên hệ, mạng xã hội, ảnh phương thức thanh toán, dòng bản quyền), header (thanh thông báo trên cùng).
- **Trang chủ kéo-thả** với các loại khối: trình chiếu ảnh, trình chiếu ảnh + chữ, banner ảnh + chữ, lưới banner nhiều bố cục (1–4 ảnh), tường thương hiệu, sản phẩm chọn tay, sản phẩm theo danh mục, sản phẩm mới nhất, sản phẩm theo tab, biểu tượng + chữ, nội dung giàu định dạng, danh sách bài viết; plugin thêm loại khối. Ảnh khối có `srcset` + `sizes` tính theo độ rộng container và số cột.
- Trang chủ riêng cho app di động.
- Đổi ngôn ngữ/tiền tệ qua route, URL có tiền tố ngôn ngữ (trừ ngôn ngữ mặc định).
- Breadcrumb.

**VaniShop hiện có**: theme `vani-base`, 19 slot, page builder trang chủ (`StorefrontBlock`, Admin → Storefront → khối trang chủ), `SitemapProvider`, `vani.cms` có link header/footer.

**Đặc tả cần làm**
1. ✅ (R1-6, 2026-11-04, Core 0.3.58) **`LinkTarget` + `LinkResolver`** (contract trong `Storefront\Contracts`): kiểu giá trị `{type, value, label?: {vi, en}}`; loại Core: `category`, `collection`, `brand`, `product` (style), `route` (danh sách route storefront công khai cho phép), `url` (chỉ `http(s)`, `mailto:`, `tel:`; chặn `javascript:`). Extension point **`LinkType`** để plugin thêm loại (`vani.cms` thêm `page`, `article`). `LinkResolver::resolve(LinkTarget, locale)` trả `{url, label, exists}`; đối tượng đã xoá/ẩn → `exists=false`, nơi render bỏ qua link. Phân giải **batch** cho cả menu (một truy vấn mỗi loại), không cache cross-request dữ liệu động (đổi slug phải thấy ngay) — chỉ memo trong request.
2. ✅ (R1-6, 2026-11-04, Core 0.3.58; sắp xếp bằng nút thay kéo-thả — [ADR-040](../19-adr/ADR-040-typed-links-menus.md)) **Menu editor** (thay "menu editor + `MenuItemType`" đang ở *Còn thiếu* của Content): Admin → Storefront → Menu: các vị trí menu do theme khai báo (`header`, `footer_1..4`, `mobile`), cây tối đa 3 cấp, kéo-thả, mỗi mục có `LinkTarget`, nhãn ghi đè, huy hiệu (Mới/Sale), ảnh (mega menu cấp 1), bật/tắt, khung giờ hiển thị. Lưu phiên bản (nháp → xuất bản, khôi phục bản trước). Storefront đọc menu đã xuất bản.
3. ✅ (R2, 2026-11-04, Core 0.3.65 — [storefront §4b](../14-storefront/storefront.md); ảnh phương thức thanh toán là nhãn chữ, chưa có kiểu trường ảnh) **Header/footer**: cấu hình theo **schema do theme khai báo** (theme `vani-base` khai báo thanh thông báo, thông tin liên hệ, mạng xã hội, ảnh phương thức thanh toán, dòng bản quyền); Admin sinh form từ schema (dùng lại `FieldDefinition`). Không cứng các trường trong Core.
4. **Bổ sung loại khối trang chủ** (trong theme hoặc plugin `vani.storefront-extras`, **không** vào Core): trình chiếu, lưới banner (bố cục 1/2/3/4 ô), tường thương hiệu, sản phẩm theo tab (mỗi tab: danh mục/bộ sưu tập/chọn tay), biểu tượng + chữ (cam kết giao hàng, đổi trả), nội dung giàu định dạng (Markdown an toàn như `vani.cms`). Đối chiếu danh sách khối đã có trong [store-content](../05-plugin/specs/store-content.md) và [storefront-extras](../05-plugin/specs/storefront-extras.md) trước, chỉ thêm khối còn thiếu.
5. ✅ (R2, 2026-11-04, Core 0.3.67 — [storefront §4d](../14-storefront/storefront.md); khối banner nhận ảnh theo URL nên chưa có `srcset` tới khi `FieldDefinition` có kiểu chọn ảnh) **Ảnh responsive cho khối**: helper/component theme sinh `srcset` từ các bề rộng Core hỗ trợ (`ImageFormat`) và `sizes` từ (kiểu container, số cột desktop, số cột mobile). Không đọc kích thước file ảnh lúc render.
6. ✅ (R2, 2026-11-04, Core 0.3.66 — [storefront §4c](../14-storefront/storefront.md)) **Breadcrumb**: component theme nhận danh sách `{label, LinkTarget|url}`; xuất cả JSON-LD `BreadcrumbList` (PDP, danh mục, thương hiệu).
7. ✅ (R1-7, 2026-11-04, Core 0.3.59–0.3.62: định tuyến, slug theo ngôn ngữ, SEO, locale đơn/khách) **URL riêng cho bản tiếng Anh** (Owner cần, 2026-11-03) — thiết kế chi tiết: [ADR-038](../19-adr/ADR-038-localized-storefront-urls.md) (Accepted 2026-11-03). Các điểm ADR chốt:
   - Tiếng Việt giữ nguyên URL hiện tại, không tiền tố (`/san-pham/{slug}`); tiếng Anh có tiền tố `/en` và **đường dẫn tiếng Anh** (`/en/products/{slug}`, `/en/category/{slug}`…) — bảng ánh xạ đường dẫn theo locale khai báo một chỗ, kể cả cho `storefrontPages` của plugin.
   - Slug theo ngôn ngữ: bản dịch `en` của style/danh mục/thương hiệu/trang có slug riêng (unique theo locale); thiếu slug `en` thì dùng slug `vi` dưới tiền tố `/en`.
   - Locale lấy **từ URL** cho trang công khai (không từ session/cookie) để trang cache CDN được theo URL; `X-Vani-Locale` giữ cho Storefront API.
   - Mỗi trang có `<link rel="alternate" hreflang="vi|en|x-default">` và canonical theo locale; sitemap có `xhtml:link` alternate; chuyển ngôn ngữ dẫn tới **URL tương ứng** của cùng đối tượng, không về trang chủ.
   - Trang chưa có bản dịch `en`: quyết định `noindex` hay hiển thị nội dung `vi` (đề xuất: hiển thị `vi` + `noindex`).
   - Giỏ, checkout, tài khoản dùng chung phiên giữa hai ngôn ngữ; email/thông báo theo locale của đơn.
   - Ảnh hưởng `LinkResolver` (§3.16-1): phân giải theo locale hiện hành.
8. **Trang chủ app**: không làm riêng; app gọi cùng endpoint khối trang chủ của Storefront API với tham số `surface=app` (khối khai báo surface hỗ trợ).

**Vị trí**: Core `Storefront` (1, 2, 3, 5, 6, 7), theme/plugin (4). **Ưu tiên**: P0 (1, 2 — header cần menu trước go-live; 7 — đổi cấu trúc URL sau khi đã được index sẽ mất thứ hạng, nên chốt trước go-live), P1 (3, 5, 6), P2 (4 phần còn thiếu, 8).

### 3.17 Content (plugin `vani.cms`)

**Tham chiếu**: trang nội dung đa ngôn ngữ; **chuyên mục bài viết** (cây một cấp, đa ngôn ngữ, SEO); bài viết thuộc chuyên mục, **gắn danh sách sản phẩm liên quan** (hiện cuối bài); lượt xem bài; khối "danh sách bài viết" trên trang chủ.

**VaniShop hiện có**: `vani.cms` trang + tin tức, hẹn giờ, xem trước, SEO, sitemap, khối trang chủ.

**Đặc tả cần làm** (bản `vani.cms` 1.3)
1. Chuyên mục tin tức (một cấp, slug, SEO, thứ tự); URL `/tin-tuc/<chuyen-muc>` (giữ `/tin-tuc/<bai-viet>` hiện có, chuyên mục và bài viết không trùng slug — kiểm tra khi lưu).
2. Gắn sản phẩm vào bài viết (tối đa 12 style, thứ tự), render bằng `ProductCards` ở cuối bài; style bị ẩn/lưu trữ thì bỏ qua.
3. ✅ `LinkType` `page`, `article` cho menu (§3.16-1) — `vani.cms` 1.3.0; `article_category` khi có chuyên mục.

**Ưu tiên**: P1 (3), P2 (1, 2).

### 3.18 AI & agent — ❄️ đóng băng

> **Owner đóng băng (2026-11-03)**: không làm, không thiết kế thêm, không thêm extension point (`AgentTool` giữ ở *Later* trong [extension-surface-v2](../04-extension/extension-surface-v2.md)), không thêm scope token hay cột chuẩn bị cho hạng mục này. Phần dưới chỉ ghi nhận kết quả nghiên cứu để dùng khi Owner mở lại bằng ADR mới.

**Tham chiếu v3**
- **Trợ lý AI trong Admin**: hội thoại theo phiên (lưu lịch sử, xem phiên của mình/của mọi người theo quyền), nhiều agent chuyên trách (sản phẩm, đơn, khách hàng, CSKH, điều phối) do một bộ phân loại ý định chọn; **công cụ** đọc/ghi dữ liệu (tra cứu, thống kê, tạo/sửa/xoá sản phẩm, đổi trạng thái đơn, xử lý hoàn tiền…); thao tác ghi **phải được nhân viên xác nhận** trước khi chạy; workflow nhiều bước có bước chờ duyệt; nhà cung cấp LLM cấu hình được (API tương thích OpenAI, model cục bộ), thử kết nối; hạn mức sử dụng; phát hiện prompt injection; che dữ liệu cá nhân trước khi gửi LLM; nhật ký có cấu trúc + tracing; bộ nhớ hội thoại có giới hạn token.
- **MCP**: máy chủ MCP (stdio và HTTP) công bố công cụ cho agent bên ngoài, xác thực bằng API key do Admin cấp.
- **Khám phá catalog cho agent**: tài liệu khám phá ở đường dẫn `/.well-known/…`, cấp token OAuth client-credentials, endpoint tìm kiếm/chi tiết/tra cứu sản phẩm theo schema JSON công bố.

**VaniShop**: `AgentTool` ở *Later* trong [extension-surface-v2](../04-extension/extension-surface-v2.md).

**Hướng đã phác (khi mở lại)** — tách hai plugin, Core chỉ thêm extension point:
1. **Core: `AgentTool`** (contract trong `Extension`): mỗi tool khai báo tên, mô tả, schema input (JSON Schema), **permission bắt buộc**, cờ `writes` (ghi dữ liệu), và `handle(input, Actor)`. Core cung cấp `AgentToolRunner`: kiểm quyền qua `Authorizer` như Admin, tool `writes=true` **luôn** cần bước xác nhận của nhân viên (token xác nhận một lần, hết hạn 10 phút), mọi lần chạy ghi audit. Tool gọi Application/Contracts, **không** truy vấn bảng trực tiếp (R30). Module Core công bố tool đọc cơ bản (tra đơn theo số/SĐT, tồn theo SKU, doanh số theo ngày từ `OrderStatistics`).
2. **Plugin `vani.ai-assistant`**: khung chat trong Admin (Inertia), cấu hình nhà cung cấp (secret qua `Settings` mã hoá), lưu phiên trong `plg_ai_*`, hạn mức theo nhân viên/ngày, che SĐT/email/địa chỉ trước khi gửi LLM (thay bằng mã tham chiếu, khôi phục khi hiển thị), chỉ gọi tool qua `AgentToolRunner`. Bắt đầu với tool **chỉ đọc**; tool ghi chỉ tạo **bản nháp** (khuyến mãi nháp, mô tả sản phẩm nháp).
3. **MCP**: plugin `vani.ai-assistant` công bố cùng bộ `AgentTool` qua MCP (HTTP), xác thực bằng token nhân viên có scope `agent` (dùng hạ tầng token của §3.19), không có API key riêng ngoài hệ thống token.
4. **Plugin `vani.agent-catalog`** (P2): endpoint chỉ đọc cho agent mua sắm bên ngoài, dữ liệu lấy từ `ProductViews` (đã có giá + ATS), rate limit riêng, không lộ giá vốn/tồn chi tiết (chỉ `available`). Chờ chuẩn giao thức ổn định trước khi làm.

**Ưu tiên**: không xếp — đóng băng.

### 3.19 Admin API

**Tham chiếu**: API quản trị gần như trùng mọi màn hình Admin (CRUD sản phẩm, danh mục, đơn, khách, cấu hình, giao diện…), xác thực bằng token nhân viên.

**VaniShop**: `/api/admin/v1` Designed; registry màn hình (`adminFormSection`…) đã thiết kế dùng chung cho Admin API ([extension-surface-v2 §4.A](../04-extension/extension-surface-v2.md)).

**Đặc tả cần làm**
1. Token nhân viên: tạo trong "Hồ sơ của tôi" (§3.3), tên, scope (đọc/ghi theo nhóm quyền), hạn dùng bắt buộc (tối đa 1 năm), chỉ hiện token một lần, lưu hash, thu hồi; mọi request qua `Authorizer` như Admin web + audit.
2. Phạm vi đợt đầu: đọc đơn/khách/sản phẩm/tồn, đổi trạng thái đơn (qua `OrderTransitions`), cập nhật giá/tồn hàng loạt (dùng `PriceImporter`/`StockImporter`). Không mở CRUD giao diện qua API.
3. `Idempotency-Key` bắt buộc cho mọi request ghi; định dạng lỗi `BusinessRuleViolation` như Storefront API; schema trong `docs/api/schemas`.

**Vị trí**: Core (mỗi module tự công bố route API của mình). **Ưu tiên**: P1 (1 — cần cho app/công cụ vận hành), P2 (2, 3).

### 3.20 Vận hành & console

| Việc trong tham chiếu | VaniShop | Quyết định |
|---|---|---|
| Bộ cài qua web, file đánh dấu đã cài | `vani:install` qua CLI | Giữ CLI ([reference-comparison §4](../02-architecture/reference-comparison.md)) |
| Tạo/đổi mật khẩu quản trị gốc | `vani:staff:create-owner` | Thêm `vani:staff:reset-password` (§3.3) |
| Cập nhật tỷ giá | — | Không làm (chỉ VND) |
| Sinh sitemap ra file | `/sitemap.xml` động + `SitemapProvider` | Giữ; nếu > 50 000 URL thì chia sitemap index — kiểm tra |
| Tự huỷ đơn chưa thanh toán, tự hoàn tất đơn | Payment hết hạn, Fulfillment hoàn tất sau hạn đổi trả | Đã có |
| Sinh từ điển dữ liệu Markdown | — | P2: `vani:docs:schema` sinh `docs/07-database/schema.generated.md` từ DB thật, chạy trong CI để tài liệu khớp schema |
| Sửa giá trị sequence (PostgreSQL) | — | Không cần (MySQL) |
| Chuyển dữ liệu từ nền tảng khác | `CatalogImporter`/`PriceImporter`/`StockImporter` | Giữ importer CSV; chuyển dữ liệu từ VaniCommerce viết script riêng một lần ngoài repo (theo [ADR-034](../19-adr/ADR-034-vanicommerce-plugin-migration.md)) |
| Phiên hết hạn phát sự kiện | — | Không cần |

## 4. Hàm toàn cục → API của VaniShop

### 4.1 Hệ tham chiếu dùng hàm toàn cục cho gì

Khoảng 60 hàm, chia nhóm (mô tả hành vi, không chép tên):

| Nhóm | Hành vi trong tham chiếu | Tương đương VaniShop hiện có | Đề xuất |
|---|---|---|---|
| **Cấu hình** | Đọc cấu hình theo khoá chấm (có điểm lọc khi đọc); đọc cấu hình plugin; nạp toàn bộ cấu hình vào config mỗi request | `Settings::get` | Helper `vani_setting()`; **không** có điểm lọc khi đọc |
| **URL** | Sinh URL Admin theo tiền tố cấu hình được; sinh URL storefront có tiền tố ngôn ngữ; phân giải link có kiểu ra URL/nhãn; URL tài nguyên tĩnh plugin; xoá tham số query khỏi URL; ghép lại URL theo ngôn ngữ | `route()` + `VANI_ADMIN_PATH` | `vani_admin_url()`, `vani_link()` (§3.16-1), `vani_asset()` (§3.4-2) |
| **Ngữ cảnh request** | Có đang ở Admin không (so tiền tố URI); có đang ở bộ cài không; tên route hiện tại / so khớp route; nhân viên hiện tại; khách hiện tại (phiên hoặc token); id phiên (ưu tiên header phiên khách); có phải app mini không | `CurrentContext`, guard `staff`/`customer`, `CartKey` | `vani_staff()`, `vani_customer()`; xác định bề mặt qua middleware gán vào context, **không** so tiền tố URI |
| **Ngôn ngữ** | Danh sách ngôn ngữ bật; ngôn ngữ hiện hành (Admin theo nhân viên, storefront theo registry → session → mặc định); gói ngôn ngữ có trên đĩa; ngôn ngữ hiện hành dạng bản ghi | `StoreLocale` | `vani_locale()` |
| **Tiền** | Định dạng tiền theo tiền tệ hiện hành; tỷ giá hiện hành; danh sách tiền tệ; mã/id tiền tệ hiện hành | `MoneyFormatter` | `vani_money(int)`; bỏ phần tiền tệ |
| **Ảnh** | Ảnh thu nhỏ theo (rộng, cao), bỏ qua URL ngoài và gif/webp; ảnh gốc; `srcset`; `sizes` theo container/cột; `sizes` cho ảnh nền phủ; ảnh của plugin; chiều cao theo tỷ lệ ảnh gốc cấu hình | `ImageFormat`, `MediaDirectory`, `/cache/media/...` | `vani_image()`; component theme cho `srcset`/`sizes` (§3.16-5) |
| **Định dạng** | Ngày giờ một mẫu; số rút gọn K/M/B/T; cắt chuỗi | `StoreClock`, `Formats` | §3.1-1; dùng `Str::limit` của Laravel |
| **Phản hồi JSON** | Hai hàm trả JSON thành công/thất bại với trạng thái + thông điệp + dữ liệu | Định dạng lỗi API + `BusinessRuleViolation` | **Không** thêm: giữ một định dạng API duy nhất |
| **Hook** | Gọi filter/action (log khi debug); đăng ký filter/action/khối HTML | `hook_filter`/`hook_action`/`add_hook_filter`/`add_hook_action`, `vani_*` | Đã có (ADR-036) |
| **Plugin** | Đường dẫn thư mục plugin; lấy đối tượng plugin theo mã; có plugin dịch máy nào bật không | `Extensions` | `vani_plugin()` chỉ đọc manifest + trạng thái bật |
| **Môi trường** | Đã cài chưa; HTTPS (đọc nhiều header); di động (user agent); IP client (header CDN); host an toàn (whitelist); so domain hiện tại với `APP_URL`; tách domain gốc; driver DB | Cấu hình Laravel | **Không** thêm helper; xem §3.1-3/4 |
| **License/nhà phát hành** | Kiểm tra mã license theo domain; URL API/website nhà phát hành | — | **Không áp dụng** |
| **Tệp** | Tạo thư mục đệ quy dưới public; nén thư mục thành zip; di chuyển thư mục; đường dẫn thư mục ảnh | `Storage` | **Không** thêm |
| **Debug** | In SQL đã ghép tham số; có debugbar không | Pail, Telescope không dùng | **Không** thêm |
| **Mảng** | Mảng kết hợp?; mảng hai chiều? | `Arr::isAssoc`, `Arr::isList` | **Không** thêm |

### 4.2 Bộ helper `vani_*` — Owner đã duyệt

Quyết định, chữ ký, quy tắc viết và phạm vi dùng: [ADR-037](../19-adr/ADR-037-vani-helpers.md) (nguồn duy nhất, không chép lại ở đây). Khác với bản đề xuất đầu:

- Bỏ helper riêng cho cấu hình plugin: `Settings::get` đã nhận namespace, plugin dùng plugin id làm namespace → `vani_setting($namespace, $key)`.
- `vani_image` và `vani_plugin` cần hai contract mới (`Catalog\Contracts\MediaUrls`, `Extension\Contracts\PluginDirectory`), vì bộ tạo URL ảnh hiện nằm ở tầng Application của Catalog và chưa có contract chỉ đọc cho thông tin plugin (R5/R7).
- `vani_link` có sau `LinkResolver` (§3.16-1); `vani_asset` có sau tài nguyên tĩnh plugin (§3.4-2).

## 5. Không áp dụng (và lý do)

| Khái niệm của hệ tham chiếu | Lý do |
|---|---|
| Đa tiền tệ + tỷ giá tự cập nhật | Bán trong nước, tiền VND số nguyên ([money](../02-architecture/money.md)); mở lại cần ADR |
| Thuế theo lớp thuế × vùng địa lý, nhiều mức | Một pháp nhân VN; VAT là plugin |
| Quốc gia/châu lục/vùng tuỳ biến | Chỉ địa giới VN (`vani.provinces-vn`) |
| Kho plugin trực tuyến, mua/tải plugin từ Admin, kiểm tra license theo domain | [ADR-026](../19-adr/ADR-026-plugin-deploy-via-code.md); mã độc quyền của Owner |
| Bộ cài qua web | CLI + CI |
| Plugin sửa bảng chuyển trạng thái đơn | Đã từng gây rò kho; bảng chuyển cố định |
| Thay/bọc HTML của view bằng hook | [ADR-025](../19-adr/ADR-025-native-storefront-ssr-slots.md) |
| Cấu hình đọc ra lọc được bởi plugin | Khó truy vết; dùng hook service tại nơi dùng |
| Giảm toàn đơn theo nhóm khách | Giá thành viên theo dòng; nếu cần thì rule khuyến mãi (§3.6) |
| Mini app nước ngoài, push qua dịch vụ nước ngoài | Zalo Mini App qua Storefront API; push là `NotificationChannel` plugin khi cần |
| Registry giá trị toàn cục theo request | `ContextScope` |
| Header id phiên khách cố định | Token giỏ bí mật ([ADR-022](../19-adr/ADR-022-guest-cart-token.md)) |

## 6. Kế hoạch refactor theo đợt

Mỗi hạng mục đi theo [Definition of Done chung](roadmap.md) (feature test, invariant test nếu đụng tiền/tồn/đơn, snapshot public API, `status.md`, `CHANGELOG-extension`). Một hạng mục = một PR, kèm checklist clean-room.

| Đợt | Hạng mục (mục tài liệu) | Module | Xong khi |
|---|---|---|---|
| **R0 — Chuẩn bị** | ✅ Owner trả lời §7 (2026-11-03); ✅ [ADR-037](../19-adr/ADR-037-vani-helpers.md) Accepted; ✅ viết [ADR-038](../19-adr/ADR-038-localized-storefront-urls.md) URL theo ngôn ngữ (§3.16-7) — ✅ Accepted; chuyển mã nguồn tham chiếu ra lưu trữ offline (§0) — Owner tự làm | docs | ✅ ADR; thư mục tham chiếu không còn trên máy dev |
| **R1 — Trước go-live (P0)** | ✅ IP client + host an toàn (§3.1-3/4) — R1-1, 2026-11-03; ✅ bảo trì + khách vãng lai (§3.2) — R1-2, 2026-11-03 (Core 0.3.49); ✅ **ẩn giá với khách chưa đăng nhập** (§3.2) — R1-3, 2026-11-03 (Core 0.3.50); ✅ nhân viên/vai trò + quên mật khẩu Admin (§3.3-1/2/4) — R1-4, 2026-11-03 (Core 0.3.51); ✅ xoá tài khoản (§3.15-1) — R1-5, 2026-11-04 (Core 0.3.57); ✅ `LinkTarget`/`LinkResolver`/`LinkType` + menu editor (§3.16-1/2) — R1-6, 2026-11-04 (Core 0.3.58); ✅ **URL tiếng Anh `/en` + hreflang** theo ADR-038 (§3.16-7) — R1-7, 2026-11-04 (Core 0.3.59–0.3.62) | Shared, Storefront, Identity, Customer, Catalog | Feature test từng mục; header theme đọc menu đã xuất bản; E2E: bật bảo trì vẫn nhận IPN; bật ẩn giá thì HTML cache, API, JSON-LD, sitemap không có giá; mọi trang công khai có hreflang + canonical đúng locale |
| **R2 — Bề mặt storefront (P1)** | Cấu hình `storefront.*` còn lại (§3.2); helper `vani_*` + contract `MediaUrls`, `PluginDirectory` (ADR-037); tài nguyên tĩnh plugin (§3.4-2); facet cấu hình được + gợi ý danh mục/thương hiệu (§3.5-1/4); chọn dòng giỏ + mua ngay + giỏ mini (§3.8); header/footer theo schema theme, ảnh responsive, breadcrumb (§3.16-3/5/6); `LinkType` của `vani.cms` (§3.17-3) | Tenancy, Extension, Catalog, Cart, Checkout, Storefront, plugin | Snapshot public API cập nhật; invariant "đơn chỉ chứa dòng đã chọn"; arch test "modules/ không gọi helper" |
| **R3 — Vận hành (P1)** | Hồ sơ nhân viên + ngôn ngữ Admin riêng + reset-password CLI (§3.3-3/5); token nhân viên (§3.19-1); thanh toán lại (§3.11-2); lý do đổi trả + khách báo đã gửi (§3.13-1/2); khối lượng giỏ cho phí ship (§3.10-2); `Weight` + số rút gọn (§3.1-1/2); plugin `vani.wishlist` (§3.15-3) | Identity, Ordering, Payment, Returns, Checkout, Shared, plugin | Như DoD; `vani.wishlist` qua bước 5 của quy trình chuyển plugin |
| **R4 — Khi có nhu cầu (P2)** | Lượt xem + đã xem (§3.5-2); thương hiệu theo chữ cái (§3.5-3); rule nhóm khách (§3.6); khách xác nhận đã nhận (§3.11-1); ảnh yêu cầu đổi trả (§3.13-3); khối trang chủ còn thiếu (§3.16-4); chuyên mục + sản phẩm trong bài viết (§3.17-1/2); `TranslationProvider` (§3.4-5); Admin API đợt đầu (§3.19-2/3); từ điển dữ liệu (§3.20) | nhiều | Mỗi mục có ADR riêng nếu mở extension point mới |
| ❄️ **Đóng băng / bỏ** | AI & agent (§3.18) — đóng băng; sản phẩm không cần giao (§3.10-1), duyệt tài khoản (§3.15-2) — không làm | — | Chỉ mở lại bằng ADR mới do Owner quyết định |

Thứ tự trong một đợt theo nguyên tắc của roadmap: **Correctness → Consistency → Extensibility → Integration → Observability → Feature**.

## 7. Quyết định của Owner (2026-11-03)

| # | Câu hỏi | Quyết định | Ảnh hưởng |
|---|---|---|---|
| 1 | Có bán sản phẩm không cần giao hàng? | **Chỉ bán hàng vật lý** | §3.10-1 không làm |
| 2 | Ẩn giá với khách chưa đăng nhập? Duyệt tài khoản mới? | **Cần ẩn giá; không duyệt tài khoản** | §3.2 `storefront.price_visibility` lên P0 (R1); §3.15-2 không làm |
| 3 | Bản tiếng Anh có URL riêng cho SEO? | **Có** | §3.16-7 lên P0, cần ADR-038 trước khi code (R0) |
| 4 | Bộ helper `vani_*`? | **Đồng ý** | [ADR-037](../19-adr/ADR-037-vani-helpers.md) Accepted; làm ở R2 |
| 5 | Trợ lý AI trong Admin? | **Tạm đóng băng** | §3.18 ❄️; `AgentTool` giữ *Later*; không thêm scope token `agent` |

## 8. Giới hạn của tài liệu

- Kiểm kê dựa trên đọc cấu trúc, route, service chính và file hàm toàn cục của hệ tham chiếu; **không** đọc toàn bộ view, JS, file ngôn ngữ (và người code cũng không cần — clean-room cấm dùng chúng).
- Phần "VaniShop hiện có" lấy từ [status](../00-overview/status.md) và danh sách `Contracts/` ngày 2026-11-03. Các mục ghi "kiểm tra … trước" là chỗ người viết chưa đối chiếu code VaniShop đến mức hàm; người làm hạng mục kiểm tra rồi cập nhật tài liệu này.
