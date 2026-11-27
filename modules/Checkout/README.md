# Checkout

> Vòng 1 · Mức domain: **Điều phối** · Tài liệu: [cart-checkout.md](../../docs/03-domains/cart-checkout.md)

## Trách nhiệm

Ghép giỏ, giá, khuyến mãi, phí giao, thuế, phương thức thanh toán thành đơn; đặt hàng idempotent; đơn thay thế khi đổi hàng.

## Bất biến

- Đặt hàng idempotent theo key; giữ hàng + tạo đơn + ghi lượt mã trong một transaction.
- Không gọi HTTP ra ngoài trong transaction đặt hàng (R12).
- Tổng tiền tính bằng `Money` qua pipeline `TotalsCalculator`; khách xác nhận lại khi tổng đổi.

## Bảng sở hữu

Không có bảng.

## Contract công bố

| Contract | Loại | Ghi chú |
|---|---|---|
| `Checkout` | interface | giữa module |
| `CheckoutRejected` | class | giữa module |
| `CheckoutValidator` | interface | tag `vani.checkout.validators`, plugin |
| `ReplacementOrders` | interface | giữa module |
| `ReplacementUnavailable` | class | giữa module |
| `ShippingRateProvider` | interface | tag `vani.checkout.shipping_providers`, plugin |
| `TaxCalculator` | interface | tag `vani.tax.calculators`, plugin |
| `TotalsCalculator` | interface | tag `vani.totals.calculators`, plugin |
| `VoucherPreview` | interface | plugin (0.5.109) |

---

Bảng sở hữu và contract lấy từ code (migration `Schema::create`, `Contracts/`, `#[PluginApi]`); arch test D5 kiểm README liệt kê đủ bảng sở hữu. "plugin" = API cho plugin (ADR-048); "giữa module" = chỉ module khác của Core dùng.
