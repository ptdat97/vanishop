# Payment

> Vòng 1 · Mức domain: **Rich** · Tài liệu: [payment.md](../../docs/10-payment/payment.md)

## Trách nhiệm

Khoản thanh toán, cổng (plugin), callback/IPN, thu khi giao, hoàn tiền, chuyển trả, đối soát lệch.

## Bất biến

- Ghi nhận tiền: khoá payment → ghi giao dịch (unique) → so số tiền → đổi trạng thái theo `PaymentStatus::transitionTo()`.
- Tổng hoàn ≤ số đã thu; chỉ hoàn khoản đang giữ tiền (`RefundRules`).
- Số tiền lệch không ghi nhận, vào "Đối soát lệch"; gọi cổng ngoài transaction.

## Bảng sở hữu

- `payments`
- `payment_transactions`
- `refunds`
- `payment_reconciliations`
- `payment_reconciliation_lines`
- `reconciliation_entries`

## Contract công bố

| Contract | Loại | Ghi chú |
|---|---|---|
| `CallbackResponder` | interface | plugin |
| `CapturesLater` | interface | plugin |
| `InvalidCallback` | class | plugin |
| `PaymentGateway` | interface | tag `vani.payment.gateways`, plugin |
| `PaymentRejected` | class | plugin |
| `Payments` | interface | plugin |
| `Reconciliations` | interface | plugin (experimental) |
| `RefundPayout` | interface | tag `vani.payment.refund_payouts`, plugin (experimental) |

## Event

`PaymentAuthorized`, `PaymentCaptured`, `PaymentFailed`, `RefundCompleted`, `RefundCreated`

---

Bảng sở hữu và contract lấy từ code (migration `Schema::create`, `Contracts/`, `#[PluginApi]`); arch test D5 kiểm README liệt kê đủ bảng sở hữu. "plugin" = API cho plugin (ADR-048); "giữa module" = chỉ module khác của Core dùng.
