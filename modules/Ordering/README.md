# Ordering

> Vòng 1 · Mức domain: **Rich (state machine)** · Tài liệu: [order.md](../../docs/09-order/order.md)

## Trách nhiệm

Đơn hàng, máy trạng thái đơn, snapshot, lệnh của nhân viên (xác nhận, huỷ, ghi chú), tín hiệu rủi ro, phiếu giao.

## Bất biến

- Trạng thái đơn chỉ đổi qua `OrderTransitions`/`OrderActions` theo máy trạng thái (R6).
- Đơn giữ snapshot tên, SKU, giá, giảm giá, địa chỉ (R15); `order_events` chỉ thêm.
- Đơn COD chờ nhân viên xác nhận (ADR-046); tự động hoá là điểm mở rộng gọi cùng lệnh.

## Bảng sở hữu

- `orders`
- `order_lines`
- `order_adjustments`
- `order_events`

## Contract công bố

| Contract | Loại | Ghi chú |
|---|---|---|
| `ConfirmationChannel` | interface | tag `vani.ordering.confirmation_channels`, plugin (experimental) |
| `CustomerOrders` | interface | giữa module |
| `OrderActionRejected` | class | plugin |
| `OrderActions` | interface | plugin (experimental) |
| `OrderReader` | interface | plugin |
| `OrderRiskSignal` | interface | tag `vani.ordering.risk_signals`, plugin (experimental) |
| `OrderRisks` | interface | plugin (experimental) |
| `OrderStatistics` | interface | plugin |
| `OrderStatusAction` | interface | tag `vani.ordering.status_actions`, plugin |
| `OrderTransitionRejected` | class | plugin |
| `OrderTransitions` | interface | plugin |
| `OrderWriter` | interface | giữa module |
| `PackingSlipTemplate` | interface | tag `vani.ordering.packing_slips`, plugin (experimental) |

## Event

`OrderCancelled`, `OrderCompleted`, `OrderConfirmed`, `OrderLinesCancelled`, `OrderPlaced`, `OrderStatusOverridden`

---

Bảng sở hữu và contract lấy từ code (migration `Schema::create`, `Contracts/`, `#[PluginApi]`); arch test D5 kiểm README liệt kê đủ bảng sở hữu. "plugin" = API cho plugin (ADR-048); "giữa module" = chỉ module khác của Core dùng.
