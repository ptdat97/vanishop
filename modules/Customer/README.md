# Customer

> Vòng 1 · Mức domain: **CRUD + Rich (hợp nhất tài khoản)** · Tài liệu: [customer.md](../../docs/03-domains/customer.md)

## Trách nhiệm

Tài khoản khách hợp nhất theo SĐT, đăng nhập OTP/mật khẩu, sổ địa chỉ, consent, nhóm/nhãn, ghi chú CSKH.

## Bất biến

- Một SĐT một tài khoản; hợp nhất chuyển lịch sử đơn về tài khoản đích.
- Tin marketing chỉ gửi khi có consent đúng kênh × mục đích.
- Nhãn/nhóm/ghi chú do plugin ghi luôn kèm nguồn (`system:<plugin>`) và audit.

## Bảng sở hữu

- `customers`
- `customer_addresses`
- `customer_consents`
- `customer_consent_events`
- `customer_otps`
- `customer_tokens`
- `customer_identities`
- `customer_groups`
- `customer_tags`
- `customer_notes`

## Contract công bố

| Contract | Loại | Ghi chú |
|---|---|---|
| `AuthProvider` | interface | tag `vani.customer.auth_providers`, plugin |
| `AuthProviderFailed` | class | plugin |
| `CustomerAccounts` | interface | giữa module |
| `CustomerNotes` | interface | plugin (experimental) |
| `CustomerRejected` | class | giữa module |
| `CustomerSegments` | interface | plugin |
| `CustomerSessions` | interface | giữa module |
| `Customers` | interface | plugin |
| `OtpDeliveryFailed` | class | plugin |
| `OtpSender` | interface | tag `vani.customer.otp_senders`, plugin |

## Event

`ConsentChanged`, `CustomerAnonymized`, `CustomerMerged`, `CustomerRegistered`

---

Bảng sở hữu và contract lấy từ code (migration `Schema::create`, `Contracts/`, `#[PluginApi]`); arch test D5 kiểm README liệt kê đủ bảng sở hữu. "plugin" = API cho plugin (ADR-048); "giữa module" = chỉ module khác của Core dùng.
