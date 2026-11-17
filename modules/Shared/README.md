# Shared

> Vòng 0 · Mức domain: **Shared kernel** · Tài liệu: [money.md](../../docs/02-architecture/money.md)

## Trách nhiệm

Kiểu tiền, ngữ cảnh request/actor, idempotency, số thứ tự, tiện ích giờ/ngôn ngữ/tiền của cửa hàng; điểm mở rộng trung lập thị trường (số điện thoại, địa giới, kênh cảnh báo).

## Bất biến

- Tiền là số nguyên minor unit + tiền tệ (`Money`), không float (R16).
- Mỗi lệnh có idempotency key chỉ chạy một lần (`IdempotencyStore`).
- Không biết khái niệm thương mại nào (R29).

## Bảng sở hữu

- `idempotency_keys`
- `number_sequences`

## Contract công bố

| Contract | Loại | Ghi chú |
|---|---|---|
| `AddressDirectory` | interface | tag `vani.address.directories`, plugin |
| `AlertChannel` | interface | tag `vani.alert-channels`, plugin |
| `Metrics` | interface | giữa module |
| `PhoneNumberPolicy` | interface | tag `vani.phone.policies`, plugin |
| `PluginApi` | class | giữa module |
| `ShippingAddresses` | interface | giữa module |

---

Bảng sở hữu và contract lấy từ code (migration `Schema::create`, `Contracts/`, `#[PluginApi]`); arch test D5 kiểm README liệt kê đủ bảng sở hữu. "plugin" = API cho plugin (ADR-048); "giữa module" = chỉ module khác của Core dùng.
