# Plugin spec: Store Omnichannel (`vani.store-omnichannel`)

> Trạng thái: **Planned** (đợt P2). Đây là yêu cầu đầu vào cho plugin.

## Core cung cấp sẵn

Location `type = store`; capability `pickup_in_store`, `ship_online_orders`, `accept_returns`; contract `FulfillmentMethod`, `SourcingStrategy`; `AvailabilityReader` theo location; phân quyền theo scope `location` ([inventory](../../08-inventory/inventory.md), [fulfillment](../../09-order/fulfillment.md)).

## Tính năng

| Tính năng | Mô tả | Extension point | Đợt |
|---|---|---|---|
| **Tra tồn tại cửa hàng** | PDP hiển thị "còn hàng tại 3 cửa hàng gần bạn" (theo tỉnh/thành, không hiển thị con số chính xác) | `AvailabilityReader`, slot PDP, storefront route | P2 |
| **BOPIS** (Click & Collect) | Khách chọn cửa hàng nhận; cửa hàng xác nhận soạn hàng → SMS/ZNS "hàng đã sẵn sàng" → khách xuất trình mã để nhận | `FulfillmentMethod` (`pickup`), `SourcingStrategy`, `ShipmentRecorder`, `NotificationChannel` | P2 |
| **Ship-from-store** | Cửa hàng là location xuất đơn online | `SourcingStrategy` | P3 |
| **Endless aisle** | Nhân viên cửa hàng đặt online hộ khách khi cửa hàng hết size | Admin page + Storefront API | P3 |
| **Trả hàng online tại cửa hàng** | Trả/đổi đơn online tại cửa hàng có `accept_returns` | `ReturnPolicy`, Returns contract | P3 |

## Vận hành

- **Store App**: trang Inertia trong Admin, quyền `store_staff` giới hạn theo location.
- SLA BOPIS: soạn hàng trong 2 giờ làm việc; giữ hàng tại quầy 3 ngày, quá hạn thì tự huỷ (qua `OrderTransitions`) và hoàn tiền.

## Bảng

`plg_store_pickups` (order_id, location_id, pickup_code, ready_at, collected_at, expires_at), `plg_store_tasks`.
