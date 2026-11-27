# Customer

> Vòng 1 · Mức domain: **CRUD + Rich (hợp nhất tài khoản)** · Tài liệu: [customer.md](../../docs/03-domains/customer.md)

## Trách nhiệm

Tài khoản khách hợp nhất (email là cách đăng nhập mặc định: đăng ký qua link xác minh, email + mật khẩu, quên mật khẩu; hồ sơ khách vãng lai theo SĐT), khung OTP cho plugin đăng nhập bằng SĐT, sổ địa chỉ, consent, nhóm/nhãn, ghi chú CSKH.

## Bất biến

- Một SĐT một tài khoản, một email một tài khoản; hợp nhất chuyển lịch sử đơn về tài khoản đích.
- Tài khoản email chỉ tạo khi bấm link xác minh; chỉ email đã xác minh mới dùng để gộp đơn khách vãng lai.
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
| `CustomerDirectory` | interface | plugin (0.5.110) |
| `CustomerNotes` | interface | plugin (experimental) |
| `CustomerRejected` | class | giữa module |
| `CustomerSegments` | interface | plugin |
| `CustomerSessions` | interface | giữa module |
| `Customers` | interface | plugin |
| `OtpDeliveryFailed` | class | plugin |
| `OtpSender` | interface | tag `vani.customer.otp_senders`, plugin |
| `PhoneOtpLogin` | interface | plugin (experimental) |

## Event

`ConsentChanged`, `CustomerAnonymized`, `CustomerMerged`, `CustomerRegistered`

---

Bảng sở hữu và contract lấy từ code (migration `Schema::create`, `Contracts/`, `#[PluginApi]`); arch test D5 kiểm README liệt kê đủ bảng sở hữu. "plugin" = API cho plugin (ADR-048); "giữa module" = chỉ module khác của Core dùng.
