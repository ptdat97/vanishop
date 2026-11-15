# Fulfillment & Shipment

> Trạng thái: **Partially Implemented (slice 9)**. Ở lại Core, làm gọn cho SME bằng giao diện: [ADR-043](../19-adr/ADR-043-fulfillment-stays-in-core.md). Vai trò ODO đang hoãn ([ADR-007](../19-adr/ADR-007-erp-integration.md)), nên mặc định `fulfillment.mode = internal`.
>
> **Đã có:** module `modules/Fulfillment`: `shipments` (theo location, carrier, mã vận đơn unique theo carrier, COD, `lock_version`), `shipment_lines`, `shipment_events` (append-only, unique `(shipment_id, event_id)` chống webhook trùng); trạng thái chuẩn hoá chỉ đi tiến (webhook cũ/sai thứ tự được lưu vết nhưng không làm lùi); `ShippingCarrier` (tag `vani.shipping.carriers`) + `CarrierCapabilities` (đặt tự động, webhook, huỷ); carrier Core `manual` (nhân viên nhập mã vận đơn, cập nhật trạng thái); `SourcingStrategy` mặc định **`reserved_locations`** (giao từ đúng kho đã giữ hàng lúc đặt, một vận đơn mỗi kho — thay cho `priority_first_fit`, vì Inventory đã chọn kho theo priority khi giữ hàng); đơn xác nhận → tự tạo vận đơn → đơn `processing` (`VANI_FULFILLMENT_AUTO_CREATE`); đặt vận đơn qua API hãng bằng job sau commit (`BookShipmentJob`, thử 3 lần → `booking_failed`); webhook chung `/api/shipping/{carrier}/webhook`; `fulfillment_status` của đơn tính lại sau mỗi thay đổi; **commit giữ hàng khi mọi vận đơn đã rời kho**; hàng hoàn về → nhập lại kho (`InventoryReturns`, movement `return`); giao thành công có COD → Payment ghi `cod_collected`; `vani:orders:complete-delivered` (mỗi giờ: giao hết + quá `VANI_RETURN_WINDOW_DAYS` ngày → `completed`); đơn huỷ → huỷ vận đơn chưa lấy hàng; Admin brand workspace (vận đơn, nhập mã, cập nhật trạng thái, huỷ, tạo lại) + panel "Giao hàng" trên trang đơn; bộ contract test `ShippingCarrierContract`; E2E browse → giỏ → COD → giao → hoàn tất.
>
> **Chưa có:** `FulfillmentMethod` (chỉ có giao hàng; nhận tại cửa hàng là plugin), chuyển giữ hàng giữa kho khi sourcing khác kho đã giữ, chế độ `external` (Integration API), COD chia theo vận đơn khi tách nhiều kho (hiện toàn bộ COD ở vận đơn đầu), đổi địa chỉ đồng bộ sang hãng khi vận đơn đã đặt, in nhãn hàng loạt.

## 1. Trách nhiệm

| Core (`modules/Fulfillment`) | Plugin |
|---|---|
| Shipment, shipment line, trạng thái chuẩn hoá, lịch sử | Hãng vận chuyển (`vani.ghn`, `vani.ghtk`…) |
| `SourcingStrategy` mặc định `reserved_locations` | Sourcing nâng cao, ship-from-store |
| `FulfillmentMethod` mặc định `delivery` | `pickup` (BOPIS) |
| Carrier `flat_rate` (bảng phí) + `manual` (nhập mã vận đơn) | Đối soát COD (`vani.cod-reconciliation`) |
| Chế độ `internal` / `external` (hệ thống ngoài xử lý qua Integration API) | Connector ODO |

## 2. Contract

```php
interface ShippingCarrier
{
    public function code(): string;                                        // 'flat_rate', 'manual', 'ghn'...
    public function isAvailable(ShippingContext $ctx): bool;
    /** @return list<ShippingQuote> dịch vụ + phí (Money) + ETA */
    public function quote(ShipmentDraft $draft): array;
    public function createShipment(ShipmentData $shipment): CarrierShipment;  // mã vận đơn, nhãn in
    public function cancel(ShipmentData $shipment): void;
    public function parseWebhook(Request $request): CarrierEvent;           // trạng thái đã chuẩn hoá
}

interface SourcingStrategy
{
    public function code(): string;
    /** @return list<AllocationProposal> location + dòng + số lượng */
    public function allocate(SourcingRequest $request): array;
}

interface FulfillmentMethod
{
    public function code(): string;                                        // 'delivery', 'pickup'
    public function isAvailable(CheckoutData $checkout): bool;
    public function requiresShippingAddress(): bool;
}
```

Trạng thái vận đơn chuẩn hoá: `created`, `picked_up`, `in_transit`, `out_for_delivery`, `delivered`, `failed_attempt`, `returning`, `returned`, `cancelled`.

## 3. Flow (internal)

### 3.0 Chốt ② — nhân viên đóng gói và đẩy lên hãng ([ADR-046](../19-adr/ADR-046-staffed-operations-vietnam.md))

Shop SME lấy hàng theo đợt (sáng/chiều). Vận đơn chỉ có ở hãng khi hàng đã đóng gói:

| Bước | Nhân viên | Trạng thái vận đơn | Hệ thống |
|---|---|---|---|
| Đơn được xác nhận | — | `pending_booking` ("Chờ đóng gói") | Tạo vận đơn nháp: kho, dòng, số tiền thu hộ; đơn `processing`. **Không** gọi hãng |
| Lọc và in | Lọc hàng đợi "Chờ đóng gói" theo hãng/ngày → chọn nhiều → in phiếu giao (mã vạch số đơn) | `pending_booking` | Phiếu gồm dòng hàng, COD, địa chỉ |
| Đóng gói, đẩy lên hãng | Hãng API: chọn các đơn đã đóng gói → **Đẩy lên hãng** (hàng loạt). Hãng nhập tay: tạo đơn ở web hãng (xuất file) → nhập mã vận đơn (từng đơn hoặc nhập file) | `created` (có mã) | Hãng API: `BookShipmentJob` sau commit, lỗi → `booking_failed` vào hàng đợi "Sự cố giao hàng" |
| Bàn giao shipper | Chọn nhiều → **Đã bàn giao** (`bulk-hand-over`) hoặc webhook của hãng | `picked_up` | Chốt hàng đang giữ (trừ tồn) |
| Giao / hoàn | — (hãng báo) hoặc nhân viên cập nhật tay | `delivered` / `returned` | COD ghi nhận; hàng hoàn nhập lại kho |

- **Không** có trạng thái "đã đóng gói" riêng: đóng gói nằm giữa `pending_booking` và `created`.
- Huỷ đơn khi vận đơn còn `pending_booking`/`created` → huỷ vận đơn (hãng API: gọi huỷ ở hãng), nhả hàng giữ.
- **Bật tự động khi lớn lên** (điểm mở rộng, không gỡ): `vanishop.fulfillment.booking` = `manual` (mặc định) · `on_create` (đặt ngay khi tạo vận đơn, hãng phải có `autoBooking`); plugin nghe `ShipmentCreated` hoặc chạy theo lịch (vd. đặt theo đợt 10h/15h) và gọi lệnh "Đẩy lên hãng" — cùng job, cùng idempotent, cùng hàng đợi sự cố như nhân viên bấm.
- **Đã làm (0.4.54):** cấu hình `booking` (mặc định `manual`), contract `Fulfillment\Contracts\ShipmentBooking::pushToCarrier` (nút, hàng loạt `bulk-push`, plugin dùng chung), nút "Đẩy lại lên hãng" cho vận đơn đặt lỗi. Chưa có: lọc danh sách đơn theo hãng.

### 3.1 Luồng kỹ thuật

```mermaid
sequenceDiagram
    participant O as Ordering
    participant F as Fulfillment
    participant I as Inventory
    participant J as Job (queue)
    participant C as Carrier plugin
    O-->>F: OrderConfirmed (event)
    F->>F: SourcingStrategy.allocate
    F->>I: chuyển reservation sang location được chọn (nếu khác)
    F->>F: tạo Shipment(status=pending_booking) + OrderTransitions(processing)  [1 transaction]
    Note over F,J: booking=manual (mặc định): chờ nhân viên bấm "Đẩy lên hãng"; on_create/plugin: ngay hoặc theo đợt
    F-->>J: dispatch BookShipment (afterCommit)
    J->>C: createShipment (timeout 10s, retry)
    C-->>J: tracking_number
    J->>F: ShipmentRecorder.booked
    C-->>F: webhook trạng thái → inbox → ShipmentRecorder.updateStatus
    F->>I: picked_up (manual: shipped) → InventoryReservation.commit
```

- Đặt vận đơn ở hãng **luôn chạy sau commit**, trong job idempotent theo `shipment_id` (dùng mã shipment làm mã đơn hàng phía hãng để hãng tự khử trùng lặp).
- Hãng lỗi quá số lần thử → shipment `booking_failed`, cảnh báo, nhân viên đặt lại hoặc chuyển sang `manual`.
- `fulfillment_status` của đơn được **tính lại** từ các shipment sau mỗi thay đổi.

## 4. Chế độ external (khi có ODO hoặc WMS)

Đối tác nhận `order.confirmed` (webhook hoặc pull), rồi gửi `acknowledgements`, `fulfillments` qua Integration API ([integration-platform §4](../11-integration/integration-platform.md)). Core vẫn giữ invariant: shipment tham chiếu dòng đơn có thật, số lượng giao ≤ số lượng đặt.

## 5. Dữ liệu và invariant

| Bảng | Invariant (DB / App) |
|---|---|
| `shipments(order_id, location_id, carrier_code, service_code, tracking_number, cod_amount, status, lock_version)` | DB: unique `(carrier_code, tracking_number)` khi không null; FK `order_id` |
| `shipment_lines(shipment_id, order_line_id, quantity)` | DB: `quantity > 0`; App: Σ số lượng mọi shipment của một dòng ≤ `order_lines.quantity` (khoá dòng đơn) |
| `shipment_events` | Append-only; unique `(shipment_id, carrier_event_id)` để khử webhook trùng |

## 6. Kiểm thử

- Contract test `ShippingCarrier` (plugin phải pass): quote trả Money, webhook parse đúng trạng thái chuẩn.
- Feature: đơn xác nhận → shipment → giao thành công → reservation được commit; hãng lỗi → `booking_failed`.
- Webhook trùng hoặc đến sai thứ tự không làm trạng thái đi lùi.
