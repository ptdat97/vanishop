# Nhất quán: transaction, idempotency, concurrency

> Trạng thái: **Designed**. Quyết định: [ADR-013](../19-adr/ADR-013-outbox-inbox.md), [ADR-014](../19-adr/ADR-014-idempotency.md).

## 1. Transaction boundary

- **Một Application Command = tối đa một DB transaction.** Transaction mở ở Application layer, không mở trong Controller, Domain hay Listener.
- Trong transaction **không** gọi HTTP, không gửi mail, không đẩy queue trực tiếp. Việc ra ngoài ghi vào **outbox**; domain event dispatch với `afterCommit`.
- Transaction chạm nhiều context chỉ được phép khi context gọi (Checkout) điều phối qua **Contract đồng bộ** và tất cả nằm cùng DB. Hiện chỉ có một trường hợp như vậy: `PlaceOrder`.

| Command | Phạm vi transaction | Ghi chú |
|---|---|---|
| `PlaceOrder` | Cart (khoá) + Inventory reserve + Ordering create + Payment intent + Promotion usage + Outbox | Transaction duy nhất đi qua nhiều context |
| `ConfirmPayment` (IPN) | Payment + `OrderTransitions` + Inventory commit reservation | Idempotent theo mã giao dịch cổng |
| `CancelOrder` | Ordering + Inventory release + Outbox | Hoàn tiền qua event, bất đồng bộ |
| `CreateShipment` | Fulfillment + `OrderTransitions` | Tạo vận đơn ở hãng thực hiện **sau commit** bằng job |
| `AdjustStock` | Inventory (stock level + movement) | |
| `ApplyInboundInventory` | Inventory | Kiểm tra `version` để chặn bản cũ |

## 2. Domain event

```php
DB::transaction(function () use ($command) {
    $order = $this->orders->create(/* ... */);
    $this->outbox->record(OrderPlacedMessage::from($order));   // cùng transaction
    event(new OrderPlaced(OrderData::from($order)));            // listener chạy afterCommit
});
```

- Event là **DTO bất biến**, không phải Eloquent model.
- Listener nội bộ cần xử lý ngay (ví dụ cập nhật read model) dùng `ShouldHandleEventsAfterCommit`; listener nặng hoặc có I/O thì chạy qua queue.
- Việc nào **bắt buộc không được mất** (gửi ERP, webhook đối tác) phải đi qua **outbox**, không dựa vào queue listener.

## 3. Idempotency

| Nơi | Khoá idempotency | Lưu ở |
|---|---|---|
| `POST /checkout/.../orders` | Header `Idempotency-Key` (bắt buộc) | `idempotency_keys(scope, key, request_hash, status, response_*, expires_at)` 24h. **Implemented** (`Modules\Shared\Application\IdempotencyStore`: claim ngoài transaction, `complete` trong transaction nghiệp vụ, lỗi thì nhả key; bản ghi `processing` quá 5 phút cho chạy lại; dọn bằng `vani:idempotency:prune` mỗi giờ) |
| API tạo tài nguyên (refund, shipment…) | `Idempotency-Key` | như trên |
| IPN / webhook cổng thanh toán | `(gateway, gateway_transaction_id)` | unique trên `payment_transactions` |
| Webhook / inbox tích hợp | `(system, external_event_id)` | unique trên `integration_inbox` |
| Outbox gửi đi | `message_id` (uuid) gửi kèm header `Idempotency-Key` | phía nhận |
| Job | Thiết kế idempotent (kiểm tra trạng thái trước khi làm) | — |

Cùng một key nhưng body khác (khác `request_hash`) → trả `409 idempotency.conflict`; đang xử lý → `409 idempotency.in_progress`.

## 4. Concurrency

| Tình huống | Cơ chế |
|---|---|
| Nhiều khách cùng mua SKU cuối | Pessimistic lock `SELECT … FOR UPDATE` trên `stock_levels`, khoá theo thứ tự `(location_id, variant_id)` tăng dần để tránh deadlock ([inventory](../08-inventory/inventory.md)) |
| Flash sale | Cổng chặn bằng counter Redis `DECRBY` trước khi vào transaction DB |
| Admin sửa cùng một bản ghi | Optimistic lock: cột `lock_version`, `UPDATE … WHERE id = ? AND lock_version = ?`, nếu không có dòng nào được cập nhật thì trả `409 stale_record` |
| Hai IPN đến cùng lúc | Unique `(gateway, gateway_transaction_id)` + khoá dòng `payments` |
| Inbound tồn từ ERP đến sai thứ tự | So `version`/`occurred_at`; bản cũ bị bỏ qua và ghi log |
| Outbox dispatcher nhiều worker | `SELECT … FOR UPDATE SKIP LOCKED`; một aggregate chỉ xử lý tuần tự |
| Sinh số đơn | `number_sequences` + `FOR UPDATE` |
| Deadlock | Retry transaction tối đa 3 lần với jitter (`DB::transaction($fn, attempts: 3)`) |

## 5. Xử lý lỗi

| Loại lỗi | Hành vi |
|---|---|
| Vi phạm invariant (hết hàng, chuyển trạng thái sai) | Domain exception → rollback → HTTP 409/422 với mã lỗi ổn định (`inventory.insufficient_stock`) |
| Lỗi hạ tầng tạm thời (deadlock, timeout DB) | Retry có giới hạn; quá giới hạn thì trả 503 |
| Dịch vụ ngoài lỗi | Không ảnh hưởng transaction; outbox retry với backoff → dead letter → replay ([integration-platform](../11-integration/integration-platform.md)) |
| Job lỗi | `tries` + `backoff`; hết lần thử thì vào `failed_jobs` + cảnh báo |

## 6. Kiểm thử

- Concurrency test chạy trên MySQL thật: N tiến trình cùng reserve SKU có tồn = 1 → đúng 1 thành công ([testing](../17-testing/testing.md)).
- Test idempotency: gửi cùng request 2 lần → một bản ghi, response giống nhau.
- Test outbox: rollback transaction thì không có message; commit thì có đúng 1 message.
