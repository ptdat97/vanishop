# Fulfillment

> Vòng 1 · Mức domain: **Rich (state machine)** · Tài liệu: [fulfillment.md](../../docs/09-order/fulfillment.md)

## Trách nhiệm

Vận đơn theo kho, chọn hãng, đẩy lên hãng, webhook trạng thái, thu COD, đối soát bảng kê COD của hãng (`CodStatement` → sổ `Reconciliations` của Payment), chốt tồn khi rời kho, hãng lấy hàng trả.

## Bất biến

- Trạng thái vận đơn chỉ đi tiến (`ShipmentStatus::canMoveTo`); webhook cũ không làm lùi.
- Phân bổ vận đơn khớp đúng hàng đang giữ (`AllocationCheck`); COD thu ở vận đơn đầu.
- Nhân viên đóng gói rồi mới đẩy lên hãng (ADR-046); chốt tồn khi mọi vận đơn đã rời kho.

## Bảng sở hữu

- `shipments`
- `shipment_lines`
- `shipment_events`

## Contract công bố

| Contract | Loại | Ghi chú |
|---|---|---|
| `CarrierAssignment` | interface | tag `vani.fulfillment.carrier_assignments`, plugin (experimental) |
| `DeliveryStatistics` | interface | plugin (số liệu COD giao thất bại cho báo cáo) |
| `FulfillmentRejected` | class | giữa module |
| `InvalidCarrierEvent` | class | plugin |
| `ReturnPickup` | interface | giữa module |
| `ReturnPickups` | interface | giữa module |
| `ShipmentBooking` | interface | giữa module |
| `ShipmentReader` | interface | giữa module |
| `ShippingCarrier` | interface | plugin |
| `SourcingStrategy` | interface | tag `vani.fulfillment.sourcing`, plugin |

## Event

`ShipmentCreated`, `ShipmentStatusChanged`

---

Bảng sở hữu và contract lấy từ code (migration `Schema::create`, `Contracts/`, `#[PluginApi]`); arch test D5 kiểm README liệt kê đủ bảng sở hữu. "plugin" = API cho plugin (ADR-048); "giữa module" = chỉ module khác của Core dùng.
