# Customer

> Trạng thái: **Partially Implemented** (slice Customer, 2026-10-12). Đã có: tài khoản hợp nhất theo SĐT (`customers`, unique `phone_active`/`email_normalized` qua cột generated), profile ẩn cho khách vãng lai (Checkout gắn `customer_id`), đăng nhập OTP (`OtpSender`: `email` của Core, `log` chỉ dev; `vani.zalo-zns` rồi `vani.sms-brandname` — kênh lỗi thì tự chuyển kênh kế tiếp) + mật khẩu tuỳ chọn, token Bearer ([ADR-024](../19-adr/ADR-024-customer-api-token.md)), sổ địa chỉ (`customer_addresses`, tối đa 20), consent + ledger, `customer_brand_profiles` (tính lại từ đơn), merge có audit + `CustomerMerged`, ẩn danh hoá (`CustomerAnonymized`), xuất dữ liệu, gộp giỏ khi đăng nhập, Admin khách hàng (cấp Owner). **Chưa có:** nhóm khách/tag, tab hồ sơ do plugin (`customerProfileTabs()`), đăng nhập mạng xã hội, `/me/returns`, native storefront `/tai-khoan`, lệnh dọn token/OTP hết hạn, ngày sinh/giới tính dùng cho phân khúc.
>
> Ghi chú triển khai: khách đăng nhập đặt hàng → đơn gắn khách (SĐT người nhận khác không tạo hồ sơ mới). Khách vãng lai → đơn gắn hồ sơ theo SĐT liên hệ, **kể cả khi SĐT đó đã là tài khoản** (đơn hiện trong lịch sử của chủ SĐT; người đặt không đọc được gì của tài khoản). Merge chuyển đơn (`OrderWriter::reassignCustomer`, mỗi đơn một dòng `order_events`), địa chỉ, consent; nguồn thành `merged` và giải phóng SĐT/email. Ẩn danh hoá cần OTP `delete_account`; đơn giữ snapshot. `/me/orders` giới hạn trong brand của kênh đang gọi. Khuyến mãi xem [promotion](promotion.md); loyalty là plugin ([spec](../05-plugin/specs/loyalty.md)).
>
> Định hướng [ADR-028](../19-adr/ADR-028-single-store-brand-as-catalog.md): một cửa hàng → bỏ `customer_brand_profiles` (thống kê lên hồ sơ khách), consent theo **kênh gửi × mục đích** (không theo brand). Code hiện còn hai phần này, gỡ ở slice 12.

## 1. Trách nhiệm

| Core (`modules/Customer`) | Plugin |
|---|---|
| Tài khoản hợp nhất của cửa hàng, hồ sơ, sổ địa chỉ, nhóm khách, tag | Đăng nhập mạng xã hội (`vani.social-login`) |
| Consent theo kênh gửi × mục đích | Gửi OTP qua SMS/ZNS (`vani.sms-brandname`, `vani.zalo-zns`) |
| Xác thực: mật khẩu + OTP (khung `OtpSender`, mặc định gửi email) | Loyalty, wishlist, size advisor, phân khúc RFM |
| Hợp nhất trùng lặp (merge) có audit | |
| Quyền của chủ thể dữ liệu (xuất, rút consent, xoá/ẩn danh) | |

## 2. Invariant

| Invariant | Enforce |
|---|---|
| Một SĐT (E.164) ứng với tối đa một customer đang hoạt động | DB: `UNIQUE(phone)` trên bản ghi chưa merge/ẩn danh (cột `phone_active` generated = `IF(status='active', phone, NULL)` + unique) |
| Email (nếu có) duy nhất, không phân biệt hoa thường | DB: unique trên `email_normalized` |
| Consent marketing phải có nguồn và thời điểm; rút consent có hiệu lực ngay | App: `ConsentService`; ledger `customer_consent_events` append-only |
| Merge không mất đơn: đơn của khách bị merge được chuyển sang khách đích | App: transaction `MergeCustomers` + event `CustomerMerged` |

## 3. Khách hàng hợp nhất

- **Một tài khoản cho cả cửa hàng** (một website). Trang tài khoản ở `/tai-khoan`; lịch sử đơn gồm sản phẩm mọi brand.
- **Định danh chính là số điện thoại** (chuẩn hoá E.164 `+84…`); email là phụ.
- Khách vãng lai đặt hàng → tạo **profile ẩn** theo SĐT; khi khách đăng ký bằng số đó và xác thực OTP thì hợp nhất lịch sử đơn.
- Dữ liệu khách từ POS (nếu có SĐT) đổ về qua [Integration](../11-integration/integration-platform.md), vào cùng hồ sơ.

| Nhóm dữ liệu | Trường |
|---|---|
| Định danh | phone, email, full_name, ngày sinh, giới tính |
| Địa chỉ | Sổ địa chỉ theo địa giới 2 cấp ([vietnam-localization](vietnam-localization.md)) |
| Thống kê mua | Trên hồ sơ khách: ngày mua đầu, tổng chi tiêu, số đơn (tính lại khi đặt/huỷ/merge). Mua theo brand: báo cáo từ dòng đơn |
| Consent | `customer_consents(channel[email|sms|zns|…], purpose, granted_at, revoked_at, source)` |
| Phân khúc | **Implemented (0.3.35):** nhóm khách `customer_groups` — mỗi khách **tối đa một nhóm** (`customers.customer_group_id`, quyết định bảng giá thành viên) — và tag tự do `customer_tags` (slug, tối đa 20/khách). Admin: Khách hàng → Nhóm khách; gán nhóm/tag ở trang khách; lọc danh sách theo nhóm/tag. Quyền `customers.segment`. Contract `CustomerSegments` (nhóm + tag); rule khuyến mãi `in_customer_groups`, `customer_has_tags` (plugin `vani.promotion-rules` 0.3.0) |
| Mở rộng | Tab hồ sơ do plugin thêm qua `customerProfileTabs()`; dữ liệu nhỏ trong `customers.meta.<plugin>` |

## 4. Xác thực khách

```php
interface OtpSender
{
    public function channel(): string;                          // 'email', 'sms', 'zns'
    public function isAvailable(CustomerContactData $contact): bool;
    public function send(CustomerContactData $contact, string $code, OtpPurpose $purpose): void;
}
```

- OTP 6 số, TTL 5 phút, tối đa 5 lần thử (cấu hình `vanishop.customer.otp.ttl|max_attempts`); giới hạn tần suất theo SĐT + IP (chống SMS pumping, [security](../15-security/security.md)).
- Storefront dùng session cookie; mobile/headless dùng Sanctum token.

## 5. Giao tiếp khách hàng

- Kênh: **Email (Core)**; SMS brandname, Zalo ZNS, Web push là plugin qua `NotificationChannel`.
- Template của cửa hàng (logo, màu theo theme), đa ngôn ngữ, gắn với domain event.
- Tin giao dịch (xác nhận đơn, thanh toán, giao hàng, đổi trả, OTP) không cần consent marketing.
- Tin marketing chỉ gửi khi có consent theo kênh gửi; mỗi tin có link huỷ đăng ký.

## 6. Kiểm thử

- Unit: chuẩn hoá SĐT (các dạng `09…`, `849…`, `+84 9…`).
- Feature: khách vãng lai → đăng ký cùng SĐT → lịch sử đơn được gộp; merge chuyển đơn và phát `CustomerMerged`; rút consent chặn gửi marketing.
- Security: brute-force OTP bị chặn.
