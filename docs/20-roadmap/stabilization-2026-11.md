# Ổn định trước go-live — kế hoạch theo ưu tiên (2026-11)

> Trạng thái: **In progress** (Owner giao 2026-11-12, Core 0.4.15). Thứ tự làm đúng thứ tự ưu tiên; mỗi đợt một commit, cập nhật cột "Trạng thái" khi xong.
>
> Liên quan: [api-first-refactor](api-first-refactor.md) (A0–A5 đã xong), [roadmap §4–§5](roadmap.md) (hardening, go-live gate), [reference-parity-refactor §6b](reference-parity-refactor.md) (backlog giới hạn), [production](../18-operations/production.md), [observability](../16-observability/observability.md).

| Ưu tiên | Mảng | Mục tiêu |
|---|---|---|
| **P0** | Hoàn thiện API-first | Hợp nhất API và Blade đến hết, bảo vệ hợp đồng OpenAPI khỏi thay đổi phá vỡ âm thầm, kiểm soát API storefront của plugin |
| **P1** | Ổn định Commerce Core | Checkout, Cart, Inventory, Ordering, Payment, Returns: concurrency và ranh giới transaction |
| **P1** | Production readiness | CI chạy được thật, Redis, backup/restore, logging, monitoring, security, load test |
| **P2** | Trải nghiệm Admin | Catalog, sản phẩm, giá, tồn, đơn, vận hành, báo cáo |
| **P2** | Storefront | Theme, mobile-first, SEO, tốc độ tải, checkout |

Giữ nguyên các ràng buộc đang có: kết nối ngoài đóng băng (GHTK, OAuth, OTP thật, dịch máy, Admin API), chỉ tiếng Việt nhưng giữ cơ chế i18n, SME trước, môi trường dev không cần tương thích ngược, thêm dependency phải được duyệt.

## P0 — Hoàn thiện API-first

| Đợt | Việc | Kiểm chứng | Trạng thái |
|---|---|---|---|
| **S0.1 — Một định dạng dữ liệu** | Sửa backlog L29: mọi thời điểm trong Storefront API là ISO-8601 theo múi giờ cửa hàng; Blade tự định dạng hiển thị. Sửa L30: map rỗng ra `{}` (không còn `[]`), schema bỏ `type: [object, array]` | `StorefrontApiContractTest` với `format: date-time` + kiểm múi giờ cửa hàng; Blade/Admin định dạng bằng `StoreClock::format()` | ✅ 0.4.16 — thêm `StoreClock::iso()`; DTO của Ordering/Returns/Fulfillment/Payment cũng theo ISO giờ cửa hàng |
| **S0.2 — Phủ hết hợp đồng** | Contract test gọi thật mọi thao tác trong OpenAPI (đổi trả, đã nhận hàng, thanh toán lại, đăng nhập mật khẩu, xoá tài khoản…); test tự đếm thao tác chưa được gọi | Thao tác có trong OpenAPI mà không có response thật nào được kiểm → đỏ (ngoại lệ ghi lý do: đăng nhập mạng xã hội đang đóng băng) | ✅ 0.4.16 — 55/55 thao tác; social start/complete chỉ kiểm response lỗi |
| **S0.3 — Khoá thay đổi phá vỡ** | Snapshot bề mặt hợp đồng (thao tác, trường bắt buộc, kiểu) cạnh `public-api.snapshot`; đổi snapshot phải tăng version Core và ghi CHANGELOG | Xoá/đổi kiểu một trường trong schema mà không cập nhật snapshot → đỏ | ✅ 0.4.16 — `tests/Architecture/storefront-api.snapshot` (`StorefrontApiSnapshotTest`): ghi snapshot mới khi hợp đồng đổi mà chưa tăng version → bị từ chối; version trong snapshot phải có mục CHANGELOG |
| **S0.4 — API của plugin** | Plugin có `storefrontRoutes()` khai báo OpenAPI riêng (`custom/plugin/<Name>/openapi.json`); test chung đối chiếu route `x/<slug>` với file đó và kiểm phong bì `{data}` / `error.v1` | Plugin thêm route API không khai báo → đỏ | ✅ 0.4.16 — `Extension\Testing\StorefrontApiContract`; `openapi.json` + `schemas/` cho `vani.cms`, `vani.lookbook`, `vani.wishlist`, `vani.hello-world`; arch test bắt buộc; `vani.cms` 1.5.2 trả thời điểm ISO giờ cửa hàng |

## P1 — Ổn định Commerce Core

| Đợt | Việc | Kiểm chứng | Trạng thái |
|---|---|---|---|
| **C1 — Nhóm concurrency xanh** | Sửa L28 (`ReturnConcurrencyTest` mất danh mục lý do khi truncate); chạy cả nhóm `--group=concurrency` trên MySQL | Nhóm concurrency xanh trên MySQL 8.x | ✅ — L28: `DatabaseTruncation` xoá bảng tham chiếu do migration chèn; trait `Tests\Support\KeepsReferenceData` giữ `return_reasons`, `notification_templates`. Lưu ý: nhóm `concurrency` bị loại khỏi lần chạy mặc định (`phpunit.xml`) — phải chạy `--group=concurrency` |
| **C2 — Phủ các cuộc đua còn thiếu** | Rà từng luồng ghi của Cart, Checkout, Inventory, Ordering, Payment, Returns; viết test nhiều tiến trình cho cuộc đua chưa có (vd. lượt dùng mã giảm giá, huỷ đơn ↔ callback thanh toán, nhận hàng trả ↔ hoàn tiền, điều chỉnh tồn ↔ đặt hàng) | Mỗi cuộc đua có test; lỗi tìm ra được sửa trong cùng đợt | ✅ — `CancelPaymentRaceConcurrencyTest` (huỷ đơn ↔ IPN: **lỗi thật** — IPN trả lỗi khi khách vừa huỷ, huỷ đơn lỗi khi tiền vừa được hoàn vì thanh toán muộn; đã sửa ở `PaymentService`), `OrderLifecycleConcurrencyTest` (huỷ trùng, webhook "đã giao" trùng, nhận hàng trả/hoàn tiền trùng, kiểm kê ↔ đặt hàng: đều đúng). Lượt dùng mã giảm giá, oversell, IPN trùng, đổi trả trùng đã có từ trước |
| **C3 — Ranh giới transaction** | Rà: event/job/HTTP ra ngoài không chạy trong transaction chưa commit; khoá theo thứ tự cố định để tránh deadlock | Arch/feature test cho các quy tắc rà được | ✅ 0.4.17 — `Shared\Support\ExternalCalls::guard()` trước mọi lời gọi cổng thanh toán/hãng vận chuyển/kênh OTP: ném lỗi ở dev/test khi đang trong transaction chưa commit (cả bộ test thành máy dò — không luồng nào vi phạm), production chỉ cảnh báo; `TransactionBoundaryTest`: domain event đều after-commit, lời gọi ra ngoài đều có guard. Ngoại lệ có lý do: đặt vận đơn giữ khoá khi gọi hãng (backlog L31). Thứ tự khoá chống deadlock đã có test (`ReservationConcurrencyTest`) |

## P1 — Production readiness

| Đợt | Việc | Kiểm chứng | Trạng thái |
|---|---|---|---|
| **R1 — CI chạy được thật** | Rà `ci.yml` với code hiện tại (plugin test, contract test, concurrency, type coverage); bước nào chỉ chạy được ở máy dev thì sửa | Chạy đúng các lệnh của CI ở máy dev bằng MySQL riêng | ✅ 2026-11-12 — xanh: clean-room, license, Pint (2 file cũ sai thứ tự import), **Larastan** (32 lỗi thật đã sửa: docblock lệch code — `CatalogReader::searchProducts`, `ShipmentView::$lines`, `LinkType::resolve`, thuộc tính model thiếu `@property`; lỗi thật `(string) $x['q'] ?? ''` ở `vani.cms`/`vani.hello-world`; baseline 81 → 67, không thêm mục), vue-tsc, dnd, build, test SQLite, type coverage 97,5 %, **test MySQL** (ca seeder plugin lỗi bỏ qua trên MySQL: DDL tự commit transaction bọc test — có từ 0.3.53, nghĩa là bước này của CI chưa từng xanh), concurrency, từ điển dữ liệu. `composer audit`: 9 advisory đã xử lý bằng bản vá (Owner duyệt `composer update` symfony/* 8.1.x, livewire 4.4.8). Còn `ScopeTest` đỏ vì README đang sửa |
| **R2 — Redis** | Cấu hình production mẫu dùng Redis cho cache/queue/session/lock; test lock và rate limit chạy với Redis khi có | Tài liệu + `vani:security:check` cảnh báo cấu hình không an toàn cho nhiều node | ✅ 0.4.18 — đã có từ trước: Redis bắt buộc ở production, cảnh báo driver. Thêm: `security:check` **lỗi** khi Redis của queue/session không `noeviction` hoặc không kết nối được; cache tách được sang instance riêng (`REDIS_CACHE_HOST/PORT/PASSWORD`); CI thêm Redis service và chạy cả bộ test với cache + session trên Redis (`TestCase` flush cache Redis giữa test). Máy dev không có Redis nên lượt này chỉ chạy được trên CI |
| **R3 — Backup/restore** | Diễn tập khôi phục đầy đủ (DB + `storage/app`) thành quy trình kiểm được | Test cho `vani:backup:drill`; runbook | ✅ 0.4.18 — phần DB đã có (nạp vào DB tạm, đối chiếu, bằng chứng `backup_drills`, cảnh báo quá hạn). Thêm `--with-files`: diễn tập cả `storage/app` (giải nén + đối chiếu số file); bản chỉ DB → cảnh báo. Runbook production §9 |
| **R4 — Logging & monitoring** | Log JSON có correlation id cho production; metric nghiệp vụ còn thiếu (checkout lỗi, tỷ lệ thanh toán) và cảnh báo tương ứng | Test cảnh báo mới | ✅ 0.4.18 — kênh log `json` (correlation id) cho production; metric `checkout.attempts`/`checkout.errors` (lỗi hệ thống, tách khỏi từ chối nghiệp vụ) + cảnh báo khẩn `checkout.error_rate` (> 2% trong 5 phút) đọc từ bộ đếm theo phút trên cache. Còn mục tiêu: tracing, Prometheus, slow query/deadlock |
| **R5 — Security** | Rà lại [security §5.1](../15-security/security.md) với code hiện tại (phiên cùng nguồn của API, CSRF, rate limit, upload) | `vani:security:check` + test | ✅ 0.4.18 — xem [security §5.1](../15-security/security.md) dòng 2026-11-12: CSRF phiên cùng nguồn có test thật, giới hạn dò mã giảm giá, `npm audit` trong CI, `security:check` bắt Redis eviction. Chưa: CSP (Report-Only trước), pentest bên thứ ba |
| **R6 — Load test** | Kịch bản tải cho duyệt sản phẩm → giỏ → checkout (công cụ ngoài, không thêm dependency vào app) + số liệu mục tiêu | Báo cáo một lần chạy trên máy dev | 🟡 2026-11-12 — `scripts/load/storefront.js` (k6) cập nhật theo hợp đồng sau API-first (bỏ `voucher_codes` khi đặt đơn; thêm gợi ý tìm kiếm, `/store`, beacon lượt xem). Đo đọc trên dev bằng ApacheBench: mọi endpoint trong NFR, 0 lỗi ([operations §9](../18-operations/operations.md)). **Chưa**: chạy k6 đủ kịch bản (gồm đặt đơn) trên staging — máy dev chưa cài k6 và không chạy luồng ghi vào DB dev |

## P2 — Trải nghiệm Admin

Khảo sát 2026-11-12: sửa nhanh giá/tồn ngay trong danh sách sản phẩm, thao tác hàng loạt cho đơn/vận đơn/đổi trả/tồn/giá và báo cáo doanh thu theo ngày/sản phẩm/thương hiệu/kênh/cổng **đã có**. Việc P2 Admin ưu tiên khoảng trống khi cửa hàng lớn lên và thao tác hằng ngày còn chậm.

| Đợt | Việc | Kiểm chứng | Trạng thái |
|---|---|---|---|
| **A1 — Admin không cắt dữ liệu âm thầm** | Lưới tồn, Tổng quan "hết/sắp hết", "chọn mọi kết quả lọc", xuất CSV giới hạn 5.000 biến thể mà không báo (backlog L20/L26) — shop 300 mẫu × 16 màu/size đã chạm | Test 6.000 biến thể: mọi màn hình thấy đủ, số truy vấn không tăng theo số biến thể | ✅ 0.4.19 — `VariantDirectory::searchIndex()` + `StockQueries::sellableByVariant()`; sửa hàng loạt vượt mức → báo thu hẹp bộ lọc |
| **A2 — Danh sách đầy đủ** | Vận đơn, Thanh toán, Đổi trả chỉ hiện 100 bản ghi mới nhất, không phân trang (shop 30 đơn/ngày: sau 4 ngày vận đơn cũ không mở được từ danh sách); tra số đơn từng dòng (N+1) | Test 31 bản ghi: 30 + 1, đổi số dòng, lọc giữ khi chuyển trang; số truy vấn không tăng theo số dòng | ✅ 0.4.20 — phân trang server 30/50/100 như danh sách đơn; `OrderReader::numbers()`. Còn: mã giảm giá của một khuyến mãi hiện tối đa 200 (backlog L32) |

## P2 — Trải nghiệm Admin và Storefront (ứng viên)

Chi tiết hoá khi xong P1, theo nhận xét vận hành thật. Ứng viên: sửa nhanh giá/tồn trên lưới, báo cáo doanh thu theo ngày/sản phẩm, xử lý đơn hàng loạt; storefront mobile-first, Core Web Vitals của PLP/PDP, SEO (dữ liệu có cấu trúc, sitemap), checkout một trang.
