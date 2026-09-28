# 13 — Bảo mật & Phân quyền

## 1. Tách biệt người dùng

| Loại | Guard | Bảng | Ghi chú |
|---|---|---|---|
| Khách hàng | `customer` (session + Sanctum) | `customers` | OTP/mật khẩu/social |
| Nhân viên | `staff` (session + Sanctum) | `staff_users` | **Bắt buộc 2FA** (TOTP) cho Admin; SSO Google Workspace/Microsoft (tuỳ chọn) |
| Hệ thống tích hợp | `integration` | `integration_clients` | API key + secret, HMAC, IP allowlist |

Admin chạy trên domain riêng (`admin.vani.vn`), không dùng chung cookie với storefront.

## 2. RBAC theo phạm vi (Scoped RBAC)

- **Permission**: chuỗi `<resource>.<action>` — `orders.view`, `orders.cancel`, `prices.edit`, `stock.adjust`, `promotions.publish`, `customers.export`, `integration.replay`…
- **Role**: tập permission, ví dụ:

| Role | Phạm vi điển hình | Quyền chính |
|---|---|---|
| Owner Admin | owner | Toàn quyền |
| Brand Manager | brand | Catalog, giá, KM, nội dung, báo cáo của brand |
| Merchandiser | brand | Catalog, danh mục, nội dung (không giá) |
| CSKH | owner hoặc brand | Xem/sửa đơn, đổi trả, khách hàng (không export) |
| Kế toán | legal_entity | Thanh toán, đối soát, hoá đơn, báo cáo doanh thu |
| Store Staff | location | BOPIS, nhận trả hàng, tra tồn tại location |
| Integration Operator | owner | Theo dõi & replay message |

- **Gán role kèm phạm vi**: `staff_role_assignments(staff_id, role_id, scope_type, scope_id)`. Một người có thể là Brand Manager của Lumière và CSKH toàn tập đoàn.
- Kiểm tra: `Gate::allows('orders.cancel', $order)` → Policy xác định phạm vi của `$order` (brand/location/legal_entity) và đối chiếu assignment.
- Global scope `BelongsToBrand` ([03](03-mo-hinh-da-thuong-hieu.md)) là **lớp phòng thủ thứ hai**; Policy là lớp thứ nhất.
- **Phê duyệt 2 bước** (maker–checker) cho thao tác nhạy cảm: hoàn tiền > ngưỡng, điều chỉnh tồn lớn, khuyến mãi giảm > X%, export dữ liệu khách.

## 3. Audit log

- Ghi `audit_logs` (append-only): actor, IP, user agent, hành động, đối tượng, giá trị trước/sau (đã che trường nhạy cảm), `trace_id`.
- Bắt buộc audit: đăng nhập Admin, thay đổi giá/KM, trạng thái đơn, hoàn tiền, điều chỉnh tồn, phân quyền, export dữ liệu, xem hồ sơ khách đầy đủ (truy cập PII), cấu hình thanh toán.
- Lưu tối thiểu 2 năm (hoặc theo chính sách pháp chế).

## 4. Bảo vệ dữ liệu cá nhân (NĐ 13/2023, Luật BVDLCN 2025)

| Yêu cầu | Thực hiện |
|---|---|
| Consent theo mục đích | `customer_consents` theo brand/kênh/mục đích, có nguồn & thời điểm; checkbox **không tick sẵn** |
| Thông báo xử lý | Chính sách bảo mật theo pháp nhân, liên kết tại mọi form thu thập |
| Quyền của chủ thể | Trang tài khoản: tải dữ liệu của tôi, rút consent, yêu cầu xoá tài khoản (ẩn danh hoá, giữ đơn hàng theo nghĩa vụ kế toán/thuế) |
| Tối thiểu hoá | Không thu dữ liệu không cần; không lưu số thẻ (tokenize ở cổng TT) |
| Mã hoá | TLS 1.2+; mã hoá cột nhạy cảm (số tài khoản ngân hàng hoàn tiền, secret tích hợp) bằng Laravel encrypted cast; key quản lý tách biệt |
| Che dữ liệu | SĐT/địa chỉ che một phần trong danh sách Admin, log, payload tích hợp lưu trữ |
| Lưu trữ | **Toàn bộ dữ liệu lưu tại Việt Nam** ([ADR-0008](adr/0008-ha-tang-tai-viet-nam.md)); dịch vụ SaaS nước ngoài chỉ nhận dữ liệu đã che PII, nếu không → hồ sơ đánh giá chuyển dữ liệu ra nước ngoài |
| Vi phạm | Quy trình thông báo sự cố cho cơ quan chức năng trong thời hạn quy định; runbook ở [14](14-ha-tang-van-hanh.md) |
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
