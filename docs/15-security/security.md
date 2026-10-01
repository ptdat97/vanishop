# Security

> Trạng thái: **Partially Implemented**. Đã có: guard `staff`, đăng nhập Admin + rate limit, RBAC theo phạm vi, audit log append-only. Định hướng [ADR-028](../19-adr/ADR-028-single-store-brand-as-catalog.md): một cửa hàng → bỏ phạm vi brand/pháp nhân khỏi RBAC và bỏ `BelongsToBrand` (code hiện còn, gỡ ở slice 12). Đã có thêm các kiểm soát của ADR-020 (đường dẫn Admin cấu hình được, cookie phiên riêng, IP allowlist, noindex, chính sách mật khẩu, cảnh báo IP mới). Chưa có: SSO, maker-checker, integration credentials, secret scan trong CI. **Không dùng 2FA** ([ADR-020](../19-adr/ADR-020-admin-path-no-2fa.md)).

## 1. Tách biệt người dùng

| Loại | Guard | Bảng | Ghi chú |
|---|---|---|---|
| Khách hàng | `customer` (session + Sanctum) | `customers` | OTP/mật khẩu/social |
| Nhân viên | `staff` (session + Sanctum) | `staff_users` | Mật khẩu + rate limit; **không dùng 2FA** ([ADR-020](../19-adr/ADR-020-admin-path-no-2fa.md)); SSO Google Workspace/Microsoft (tuỳ chọn) |
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
| Nhân viên cửa hàng/kho | location (Designed) | BOPIS, nhận trả hàng, tra tồn tại location |
| Integration Operator | cửa hàng | Theo dõi & replay message |

- **Gán role**: `staff_role_assignments(staff_user_id, role_id, scope_type, scope_id)` với scope `owner` (toàn cửa hàng) hoặc `location`. Permission là mã chuỗi do module/plugin khai báo (`PermissionRegistry`), vai trò lưu trong `role_permissions`; `'*'` = mọi quyền. Brand **không** là phạm vi quyền: muốn giới hạn ai sửa sản phẩm brand nào thì dùng quy trình duyệt, không dùng RBAC.
- Kiểm tra (**Implemented**, `Authorizer` + `Gate::before`):
  - `Gate::allows('orders.cancel')`: có permission ở phạm vi cửa hàng.
  - Thao tác tại một location (Designed): `Gate::allows('stock.adjust', [ScopeRef::location($id)])`.
  - Code hiện còn scope `legal_entity`/`brand` và global scope `BelongsToBrand` (mô hình cũ ADR-008) — gỡ ở slice 12.
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

## 6. Thanh toán

- **Không** lưu/không đi qua server dữ liệu thẻ → phạm vi PCI-DSS SAQ-A (redirect/hosted fields của cổng).
- Xác minh chữ ký IPN, so khớp số tiền & mã đơn, idempotent.
- Hoàn tiền: phân quyền riêng + ngưỡng phê duyệt.

## 7. Một cửa hàng, không multi-tenant

- Một bản cài đặt = một Owner = một cửa hàng (không multi-tenant SaaS, không cô lập theo brand — [ADR-028](../19-adr/ADR-028-single-store-brand-as-catalog.md)).
- **ABAC** ở mức vừa đủ: quyền = permission (RBAC) **và**, khi cần, thuộc tính bản ghi khớp với người dùng (location của nhân viên cửa hàng; seller/creator nếu có plugin). Hiện thực trong Policy, không dùng engine ABAC riêng.

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

