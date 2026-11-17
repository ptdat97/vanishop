# Cart

> Vòng 1 · Mức domain: **Rich (nhỏ)** · Tài liệu: [cart-checkout.md](../../docs/03-domains/cart-checkout.md)

## Trách nhiệm

Giỏ hàng theo token, dòng giỏ có tuỳ chọn của plugin, gộp giỏ khi đăng nhập.

## Bất biến

- Số lượng dòng ≤ tồn bán được tại thời điểm thêm (kiểm lại khi đặt hàng).
- Tuỳ chọn dòng do plugin chuẩn hoá (`CartLineOption`), chỉ giá trị JSON được.

## Bảng sở hữu

- `carts`
- `cart_lines`

## Contract công bố

| Contract | Loại | Ghi chú |
|---|---|---|
| `CartLineOption` | interface | tag `vani.cart.line_options`, plugin |
| `CartRejected` | class | plugin |
| `Carts` | interface | giữa module |
| `InvalidCartLineOption` | class | plugin |

## Event

`CartAbandoned`, `CartUpdated`

---

Bảng sở hữu và contract lấy từ code (migration `Schema::create`, `Contracts/`, `#[PluginApi]`); arch test D5 kiểm README liệt kê đủ bảng sở hữu. "plugin" = API cho plugin (ADR-048); "giữa module" = chỉ module khác của Core dùng.
