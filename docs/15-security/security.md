# Security

> Trạng thái: **Partially Implemented**. Đã có: guard `staff`, đăng nhập Admin + rate limit, RBAC theo `owner`/`location`, audit log append-only và các kiểm soát ADR-020 (đường dẫn Admin cấu hình được, cookie phiên riêng, IP allowlist, noindex, chính sách mật khẩu, audit IP mới). Brand không phải phạm vi quyền và không có global scope `BelongsToBrand` ([ADR-028](../19-adr/ADR-028-single-store-brand-as-catalog.md)). Chưa có: SSO, maker-checker, secret scan trong CI. **Không dùng 2FA** ([ADR-020](../19-adr/ADR-020-admin-path-no-2fa.md)).

## 1. Tách biệt người dùng

| Loại | Guard | Bảng | Ghi chú |
|---|---|---|---|
| Khách hàng | Web session hoặc Customer Bearer token | `customers` | OTP/mật khẩu; social login qua `AuthProvider` |
| Nhân viên | `staff` web session | `staff_users` | Mật khẩu + rate limit; **không dùng 2FA** ([ADR-020](../19-adr/ADR-020-admin-path-no-2fa.md)); SSO chưa triển khai |
| Hệ thống tích hợp | `integration` | `integration_clients` | API key + secret, HMAC, IP allowlist |

### Admin trên domain chung

Admin chạy cùng domain với storefront, dưới đường dẫn **cấu hình được** `VANI_ADMIN_PATH` ([ADR-020](../19-adr/ADR-020-admin-path-no-2fa.md)):

| Kiểm soát | Trạng thái |
|---|---|
| Đường dẫn Admin đọc từ `VANI_ADMIN_PATH` (từ chối giá trị sai định dạng hoặc trùng đường dẫn dành riêng); production dùng giá trị khó đoán; không link từ storefront; không khai trong `robots.txt`; header `X-Robots-Tag: noindex, nofollow` | Implemented |
| Rate limit đăng nhập (5 lần/phút theo email + IP) | Implemented |
| Chỉ nhân viên `active` đăng nhập được; bị khoá thì bị đăng xuất ở request kế tiếp | Implemented |
| Audit đăng nhập/đăng xuất | Implemented |
| Mật khẩu ≥ 12 ký tự (`Password::defaults()`); kiểm tra danh sách mật khẩu bị lộ (`uncompromised()`, bật mặc định ở production qua `VANI_ADMIN_CHECK_BREACHED_PASSWORDS`, chỉ gửi 5 ký tự đầu của hash SHA-1 ra ngoài) | Implemented |
| Cookie phiên Admin riêng `vanishop_admin_session`, path = đường dẫn Admin (kể cả `XSRF-TOKEN`), hết hạn sau `VANI_ADMIN_IDLE_MINUTES` (30) phút không hoạt động | Implemented (`ConfigureAdminSession`) |
| IP allowlist tuỳ chọn `VANI_ADMIN_IP_ALLOWLIST` (IP/CIDR), ngoài danh sách thì trả **404**, không lộ Admin. Khuyến nghị bật ở production. Sau CDN/proxy cần cấu hình trusted proxies để lấy đúng IP khách | Implemented (`AdminGate`) |
| Cảnh báo đăng nhập từ IP mới: audit `identity.staff.login_new_ip` + log warning; gửi thông báo (email/Telegram) chưa làm | Partially Implemented (`RecordStaffLogin`) |

> Đường dẫn bí mật chỉ giảm bị dò quét tự động, **không thay thế xác thực**. Thiếu 2FA thì an toàn của Admin phụ thuộc vào mật khẩu, nên các kiểm soát trên là bắt buộc trước go-live.

## 2. RBAC theo phạm vi (Scoped RBAC)

- **Permission**: chuỗi `<resource>.<action>` — `orders.view`, `orders.cancel`, `prices.edit`, `stock.adjust`, `promotions.publish`, `customers.export`, `integration.replay`…
- **Role**: tập permission, ví dụ:

| Role | Phạm vi | Quyền chính |
|---|---|---|
| Quản trị | cửa hàng | Toàn quyền |
| Quản lý ngành hàng | cửa hàng | Catalog (gồm brand), giá, KM, nội dung, báo cáo |
| Merchandiser | cửa hàng | Catalog, danh mục, nội dung (không giá) |
| CSKH | cửa hàng | Xem/sửa đơn, đổi trả, khách hàng (không export) |
| Kế toán | cửa hàng | Thanh toán, đối soát, hoá đơn, báo cáo doanh thu |
| Nhân viên cửa hàng/kho | Có loại phạm vi `location`; áp dụng theo từng thao tác còn một phần | BOPIS, nhận trả hàng, tra tồn tại location |
| Integration Operator | cửa hàng | Theo dõi & replay message |

- **Gán role**: `staff_role_assignments(staff_user_id, role_id, scope_type, scope_id)` với scope `owner` (toàn cửa hàng) hoặc `location`. Permission là mã chuỗi do module/plugin khai báo (`PermissionRegistry`), vai trò lưu trong `role_permissions`; `'*'` = mọi quyền. Brand **không** là phạm vi quyền: muốn giới hạn ai sửa sản phẩm brand nào thì dùng quy trình duyệt, không dùng RBAC.
- Kiểm tra (**Implemented**, `Authorizer` + `Gate::before`):
  - `Gate::allows('orders.cancel')`: có permission ở phạm vi cửa hàng.
  - `Authorizer` hỗ trợ kiểm tra quyền với `ScopeRef::location($id)`. Tuy nhiên, màn hình điều chỉnh tồn hiện kiểm tra `inventory.adjust` không kèm location; không nên coi thao tác đó đã được giới hạn theo kho.
  - RBAC dùng scope `owner` hoặc `location`; không có scope `legal_entity`/`brand` hay global scope `BelongsToBrand`.
- **Quản trị nhân viên/vai trò (Implemented, R1-4, Core 0.3.51 — [reference-parity §3.3](../20-roadmap/reference-parity-refactor.md))**: Admin → Hệ thống → Nhân viên / Vai trò, quyền `staff.manage`.
  - Tạo nhân viên = **mời qua email** (link đặt mật khẩu một lần, 24 giờ, bảng `staff_invite_tokens`); Admin không đặt mật khẩu hộ. Trạng thái `invited` → `active` khi đặt mật khẩu; `locked` khi bị khoá (đăng xuất ngay, xoay `remember_token`, huỷ link còn hạn). Không xoá cứng nhân viên.
  - Chặn: tự khoá/tự đổi vai trò của mình; khoá hoặc hạ quyền **Owner cuối cùng** (nhân viên `active` có vai trò `*` ở phạm vi owner — kiểm tra trong transaction có khoá dòng); **cấp quyền mình không có** (gán vai trò hoặc thêm quyền vào vai trò; vai trò `*` chỉ Owner cấp được). Không cấp `*` qua giao diện — chỉ vai trò hệ thống `owner_admin` (tạo bởi `vani:staff:create-owner`). Vai trò hệ thống không sửa/xoá; vai trò đang gán không xoá.
  - Hồ sơ của tôi (0.3.72): nhân viên tự đổi tên, ngôn ngữ Admin, mật khẩu (bắt buộc mật khẩu hiện tại, chính sách mật khẩu, rate limit 10/phút; đổi remember token). Email/vai trò chỉ đổi ở Admin → Nhân viên. Mất quyền truy cập email: `php artisan vani:staff:reset-password {email}` in link một lần (không in/đặt mật khẩu; nhân viên bị khoá phải mở khoá trước; audit `identity.staff.password_link_issued`).
  - Phạm vi `location`: Inventory đăng ký danh sách kho vào `Identity\Application\ScopeOptions` (Identity ở vòng 0 không đọc bảng thương mại).
  - **Quên mật khẩu Admin**: `/{admin}/quen-mat-khau` → link một lần 60 phút (`staff_password_reset_tokens`), chỉ nhân viên `active`; thông báo giống nhau dù email có hay không; giới hạn 3 lần/10 phút theo email + IP và 10 lần/10 phút theo IP; link sinh từ `APP_URL` (§5.2). Mọi thao tác ghi audit `identity.staff.*`, `identity.role.*`.
- **Phê duyệt 2 bước** (maker–checker) cho thao tác nhạy cảm: hoàn tiền > ngưỡng, điều chỉnh tồn lớn, khuyến mãi giảm > X%, export dữ liệu khách.

## 3. Audit log

- Ghi `audit_logs` (append-only): actor, IP, user agent, hành động, đối tượng, giá trị trước/sau (đã che trường nhạy cảm), `trace_id`.
- Bắt buộc audit: đăng nhập Admin, thay đổi giá/KM, trạng thái đơn, hoàn tiền, điều chỉnh tồn, phân quyền, export dữ liệu, xem hồ sơ khách đầy đủ (truy cập PII), cấu hình thanh toán.
- Lưu tối thiểu 2 năm (hoặc theo chính sách pháp chế).

## 4. Bảo vệ dữ liệu cá nhân (NĐ 13/2023, Luật BVDLCN 2025)

| Yêu cầu | Thực hiện |
|---|---|
| Consent theo mục đích | `customer_consents` theo kênh gửi/mục đích, có nguồn & thời điểm; checkbox **không tick sẵn** |
| Thông báo xử lý | Chính sách bảo mật của cửa hàng (pháp nhân vận hành), liên kết tại mọi form thu thập |
| Quyền của chủ thể | Trang tài khoản: tải dữ liệu của tôi, rút consent, yêu cầu xoá tài khoản (ẩn danh hoá, giữ đơn hàng theo nghĩa vụ kế toán/thuế) |
| Tối thiểu hoá | Không thu dữ liệu không cần; không lưu số thẻ (tokenize ở cổng TT) |
| Mã hoá | TLS 1.2+; mã hoá cột nhạy cảm (số tài khoản ngân hàng hoàn tiền, secret tích hợp) bằng Laravel encrypted cast; key quản lý tách biệt |
| Che dữ liệu | SĐT/địa chỉ che một phần trong danh sách Admin, log, payload tích hợp lưu trữ |
| Lưu trữ | **Toàn bộ dữ liệu lưu tại Việt Nam** ([ADR-018](../19-adr/ADR-018-infrastructure-vietnam.md)); dịch vụ SaaS nước ngoài chỉ nhận dữ liệu đã che PII, nếu không → hồ sơ đánh giá chuyển dữ liệu ra nước ngoài |
| Vi phạm | Quy trình thông báo sự cố cho cơ quan chức năng trong thời hạn quy định; runbook ở [operations](../18-operations/operations.md) |
| Hồ sơ đánh giá tác động | Lập và cập nhật khi thêm mục đích xử lý mới |

## 5. Bảo mật ứng dụng

- Chuẩn tham chiếu: **OWASP ASVS Level 2**, OWASP Top 10.
- Validation bằng Form Request; không `$request->all()` vào mass assignment.
- CSRF cho web, CORS chặt cho API; header bảo mật (CSP, HSTS, X-Frame-Options).
- Rate limit: đăng nhập, OTP (theo SĐT + IP, chống SMS pumping), áp voucher (chống dò mã), tra cứu đơn.
- Upload: kiểm tra MIME, giới hạn dung lượng, lưu S3 private, quét virus cho file import.
- Secret: `.env`/secret manager, không commit; xoay vòng key tích hợp định kỳ.
- Phụ thuộc: `composer audit`, `npm audit` trong CI; Dependabot/Renovate.
- Bot/scraping, flash sale: WAF/CDN (Cloudflare hoặc tương đương), captcha (Turnstile) khi nghi ngờ.
- Pentest trước go-live và hằng năm.

### 5.1 Rà soát trước go-live (2026-10-08)

| Hạng mục | Kết quả |
|---|---|
| Secret trong lịch sử git (key AWS, private key, token/password dạng chuỗi dài) | Không phát hiện; `.env*` không được track |
| `composer audit`, `npm audit --omit=dev` | 0 lỗ hổng (1 gói dev abandoned: `symplify/rule-doc-generator-contracts`, không chạy ở production) |
| Output không escape trong Blade (`{!! !!}`) | Chỉ còn: `nl2br(e(...))`, HTML Markdown đã bỏ HTML thô + chặn link `javascript:` (CMS), JSON-LD `JSON_HEX_TAG` — an toàn |
| SQL thô ghép biến | Chỉ số nguyên đã ép kiểu hoặc giá trị enum cố định — không có injection |
| Đăng nhập Admin | Khoá tạm sau 5 lần sai, allowlist IP, cookie phiên riêng giới hạn đường dẫn, idle timeout, chặn mật khẩu bị lộ (ADR-020) |
| Header bảo mật | **Đã thêm** `App\Http\Middleware\SecurityHeaders`: `X-Content-Type-Options: nosniff`, `X-Frame-Options` (Admin `DENY`, storefront `SAMEORIGIN`), `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy`, `Strict-Transport-Security` khi HTTPS + production (`VANI_HSTS_MAX_AGE`). Không ghi đè header đã có (CDN/plugin) |
| Cấu hình production | **Đã thêm** `php artisan vani:security:check` — lỗi (mã thoát 1): `APP_ENV`, `APP_DEBUG`, `APP_KEY`, `APP_URL` https, `SESSION_SECURE_COOKIE`, đường dẫn Admin mặc định, mailer log/array, safe mode, OTP ghi log; cảnh báo: allowlist Admin, token health, trusted proxies, queue/cache/session driver, đích backup, email cảnh báo, hook strict |
| CSP | **Chưa bật**: layout có `<style>` token theme và JSON-LD inline, Inertia/Vite. Bước tiếp: `Content-Security-Policy-Report-Only` với nonce cho script/style inline, theo dõi một thời gian rồi chuyển sang chặn |
| Pentest bên thứ ba | Chưa — cần trước go-live (go-live gate) |

### 5.2 IP client và host an toàn (R1-1, 2026-11-03)

| Kiểm soát | Thực hiện |
|---|---|
| **IP client** | Mọi nơi cần IP (rate limit, audit, OTP, allowlist Admin, cảnh báo đăng nhập IP mới, allowlist Integration client, log webhook) chỉ dùng `$request->ip()`. Không đọc trực tiếp `CF-Connecting-IP`, `X-Forwarded-For`, `X-Real-IP`, `True-Client-IP` hay `$_SERVER` — khách tự đặt được. |
| Proxy tin cậy | `VANI_TRUSTED_PROXIES` (IP/CIDR phân tách dấu phẩy, hoặc `*` khi app chỉ nhận traffic qua LB) → `TrustProxies::at()` trong `AppServiceProvider`. **Trống = không tin proxy nào**: `X-Forwarded-*` bị bỏ qua, IP là IP kết nối. Chỉ header chuẩn `X-Forwarded-*` được dùng; CDN (Cloudflare…) phải chuyển IP khách vào `X-Forwarded-For` và IP của CDN/LB phải nằm trong danh sách. `vani:security:check` cảnh báo khi biến trống. |
| **Host an toàn** | Ngoài môi trường `local`, `AppServiceProvider::useAppUrlOrigin()` gọi `URL::useOrigin(APP_URL)` + `URL::forceScheme()` cho **mọi** request: link trong email/thông báo, `robots.txt`, `sitemap.xml` (có cache), return URL gửi cổng thanh toán, link xem trước có chữ ký, canonical/JSON-LD và HTML cache đều sinh từ `APP_URL`, không từ header `Host`/`X-Forwarded-Host` — chặn host header injection và đầu độc cache. |
| Vì sao không dùng `TrustHosts` | Chặn request sai `Host` bằng lỗi 400 cũng làm hỏng health check của LB gọi theo IP và cần danh sách host riêng; ép origin cho URL sinh ra đã đủ cho mục tiêu (không link nào mang host lạ) và đơn giản hơn. Có thể bật thêm ở tầng web server/LB nếu cần. |
| Dev | Ở `local` không ép origin để truy cập bằng host khác `APP_URL` (artisan serve, Herd, laragon) không vỡ asset. Staging/production phải đặt `APP_URL` đúng domain công khai (link ký chỉ hợp lệ khi request đến đúng host đó). |

Test: `tests/Feature/Security/SafeHostAndClientIpTest.php`, `tests/Feature/TrustedProxiesTest.php`.

## 6. Thanh toán

- **Không** lưu/không đi qua server dữ liệu thẻ → phạm vi PCI-DSS SAQ-A (redirect/hosted fields của cổng).
- Xác minh chữ ký IPN, so khớp số tiền & mã đơn, idempotent.
- Hoàn tiền: phân quyền riêng + ngưỡng phê duyệt.

## 7. Một cửa hàng, không multi-tenant

- Một bản cài đặt = một Owner = một cửa hàng (không multi-tenant SaaS, không cô lập theo brand — [ADR-028](../19-adr/ADR-028-single-store-brand-as-catalog.md)).
- **ABAC** ở mức vừa đủ: quyền = permission (RBAC) **và**, khi cần, thuộc tính bản ghi khớp với người dùng (vd. location của nhân viên cửa hàng). Hiện thực trong Policy, không dùng engine ABAC riêng.

## 8. Integration credentials

| Yêu cầu | Thiết kế |
|---|---|
| Danh tính | Mỗi hệ thống ngoài là một Integration Client ([integration-platform §3](../11-integration/integration-platform.md)) |
| Scope | Theo tài nguyên + hành động (`orders:read`, `inventory:write`…) |
| Data scope | Location (khi đối tác chỉ quản một kho/cửa hàng) |
| Ownership | Chỉ authority của loại dữ liệu mới được ghi |
| Ký request | HMAC-SHA256 trên `timestamp + "." + body`, lệch thời gian ≤ 5 phút, chống replay bằng nonce/`Idempotency-Key` |
| Webhook đi | Ký cùng cơ chế bằng secret của subscription |
| Webhook vào (cổng TT, hãng VC) | Xác thực theo chuẩn của từng dịch vụ trong plugin; sai chữ ký → 401 + log `security` |
| IP allowlist, rate limit | Theo client |
| Xoay vòng key | Hai key hoạt động song song; tạo key mới → đối tác chuyển → thu hồi key cũ; key hết hạn mặc định 12 tháng; có cảnh báo trước 30 ngày |
| Lưu trữ | Secret chỉ lưu hash (key của client) hoặc mã hoá (credential gọi ra ngoài, trong `settings` với `is_encrypted`) |

## 9. Secret

- Không để secret trong source code, fixture hay log (rule R21). CI chạy secret scan (gitleaks).
- Secret hạ tầng nằm trong `.env`/secret manager của môi trường; credential của plugin nằm trong settings đã mã hoá, key mã hoá tách khỏi DB.
- Xoay vòng `APP_KEY` theo quy trình có `APP_PREVIOUS_KEYS` (Laravel hỗ trợ), không làm mất dữ liệu đã mã hoá.
