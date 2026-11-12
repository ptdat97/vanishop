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
| **C1 — Nhóm concurrency xanh** | Sửa L28 (`ReturnConcurrencyTest` mất danh mục lý do khi truncate); chạy cả nhóm `--group=concurrency` trên MySQL | Nhóm concurrency xanh trên MySQL 8.x | |
| **C2 — Phủ các cuộc đua còn thiếu** | Rà từng luồng ghi của Cart, Checkout, Inventory, Ordering, Payment, Returns; viết test nhiều tiến trình cho cuộc đua chưa có (vd. lượt dùng mã giảm giá, huỷ đơn ↔ callback thanh toán, nhận hàng trả ↔ hoàn tiền, điều chỉnh tồn ↔ đặt hàng) | Mỗi cuộc đua có test; lỗi tìm ra được sửa trong cùng đợt | |
| **C3 — Ranh giới transaction** | Rà: event/job/HTTP ra ngoài không chạy trong transaction chưa commit; khoá theo thứ tự cố định để tránh deadlock | Arch/feature test cho các quy tắc rà được | |

## P1 — Production readiness

| Đợt | Việc | Kiểm chứng | Trạng thái |
|---|---|---|---|
| **R1 — CI chạy được thật** | Rà `ci.yml` với code hiện tại (plugin test, contract test, concurrency, type coverage); bước nào chỉ chạy được ở máy dev thì sửa | Chạy đúng các lệnh của CI ở máy dev bằng MySQL riêng | |
| **R2 — Redis** | Cấu hình production mẫu dùng Redis cho cache/queue/session/lock; test lock và rate limit chạy với Redis khi có | Tài liệu + `vani:security:check` cảnh báo cấu hình không an toàn cho nhiều node | |
| **R3 — Backup/restore** | Diễn tập khôi phục đầy đủ (DB + `storage/app`) thành quy trình kiểm được | Test cho `vani:backup:drill`; runbook | |
| **R4 — Logging & monitoring** | Log JSON có correlation id cho production; metric nghiệp vụ còn thiếu (checkout lỗi, tỷ lệ thanh toán) và cảnh báo tương ứng | Test cảnh báo mới | |
| **R5 — Security** | Rà lại [security §5.1](../15-security/security.md) với code hiện tại (phiên cùng nguồn của API, CSRF, rate limit, upload) | `vani:security:check` + test | |
| **R6 — Load test** | Kịch bản tải cho duyệt sản phẩm → giỏ → checkout (công cụ ngoài, không thêm dependency vào app) + số liệu mục tiêu | Báo cáo một lần chạy trên máy dev | |

## P2 — Trải nghiệm Admin và Storefront

Chi tiết hoá khi xong P1, theo nhận xét vận hành thật. Ứng viên: sửa nhanh giá/tồn trên lưới, báo cáo doanh thu theo ngày/sản phẩm, xử lý đơn hàng loạt; storefront mobile-first, Core Web Vitals của PLP/PDP, SEO (dữ liệu có cấu trúc, sitemap), checkout một trang.
