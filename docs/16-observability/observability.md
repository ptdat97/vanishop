# Observability

> Trạng thái: **Partially Implemented** (Core 0.3.30). Đã có: correlation id ghi vào mọi dòng log; metric tối thiểu qua contract `Shared\Contracts\Metrics` (ghi vào Pulse), thẻ Pulse "Thương mại"; health check tổng hợp `GET /health`; cảnh báo tự động `vani:alerts:check` (§7). Chưa có: tracing, Prometheus/Grafana.

## 1. Mục tiêu

Mọi flow phải truy vết được từ đầu đến cuối bằng **một correlation id**:

```text
Request → Command → Domain Event → Outbox → Worker → Connector → External API → Webhook trả về → Inbox
```

## 2. Correlation ID

| Bước | Cách mang correlation id |
|---|---|
| HTTP vào | Middleware đọc `X-Correlation-Id` (nếu từ client tin cậy) hoặc sinh ULID; trả lại trong header response |
| Trong process | Laravel `Context::add('correlation_id', …)`: tự có trong mọi log và **tự truyền sang queued job** |
| Command / Domain event | DTO có `correlationId`; `order_events`, `stock_movements`, `audit_logs` lưu cột `correlation_id` |
| Outbox / Inbox | Cột `correlation_id` |
| Gọi ra ngoài | Header `X-Correlation-Id` + `Idempotency-Key` |
| Webhook vào | Dùng lại id của message gốc nếu hệ thống ngoài gửi kèm; nếu không thì dùng `external_event_id` và liên kết qua `external_references` |
| Scheduler / CLI | Sinh id mới cho mỗi lần chạy |

## 3. Logs

- JSON có cấu trúc, mỗi dòng có `timestamp`, `level`, `correlation_id`, `source` (nguồn đơn khi có), `actor`, `module`, `plugin` (nếu có), `message`, `context`.
- **Không log PII thô và secret**: SĐT, email, địa chỉ bị che (`09*****123`); token và chữ ký bị loại bỏ.
- Kênh log: `app`, `security` (đăng nhập, sai chữ ký, từ chối quyền), `integration`, `audit` (lưu DB, không phải file), `vani.deprecation`.
- Log tập trung lưu tại VN ([ADR-018](../19-adr/ADR-018-infrastructure-vietnam.md)), giữ 30 ngày nóng + 180 ngày lạnh.

## 4. Metrics

### 4.1 Metric tối thiểu đã có (0.3.25)

Module ghi qua `Modules\Shared\Contracts\Metrics` (`increment`, `gauge`), không phụ thuộc hệ giám sát. Mặc định `App\Observability\PulseMetrics` ghi vào Pulse, với kiểu `vani.<tên>` (counter dùng `Pulse::record`, gauge dùng `Pulse::set`). Đổi sang Prometheus/OTel chỉ cần đổi binding. Tên metric dưới đây là **công khai**, dashboard và cảnh báo dựa vào chúng. `key` là chiều phân nhóm ngắn, không chứa dữ liệu cá nhân hay id đơn.

| Metric | Loại | Key | Nguồn |
|---|---|---|---|
| `orders.created` | counter | — | `OrderPlaced` |
| `orders.failed` | counter | mã lỗi checkout | `CheckoutService::placeOrder` bị từ chối |
| `orders.cancelled` | counter | nguồn huỷ | `OrderCancelled` |
| `orders.lines_cancelled` | counter | — | `OrderLinesCancelled` |
| `payments.captured` / `payments.failed` | counter | mã cổng | `PaymentCaptured` / `PaymentFailed` |
| `payments.refunded` | counter | — | `RefundCompleted` |
| `shipments.delivered` / `shipments.returned` / `shipments.failed_attempt` | counter | mã hãng | `ShipmentStatusChanged` |
| `inventory.reservation_failed` | counter | `insufficient` / `no_stock_record` | `ReservationService::reserve` |
| `integration.delivery_failed` | counter | target | `OutboxWorker` (lần gửi lỗi) |
| `integration.event_replay` | counter | `outbox` / `inbox` | `ReplayService` |
| `integration.event_reconciliation_mismatch` / `integration.event_rebuilt` | counter | — | `vani:integration:reconcile-orders` |
| `payments.reconciliation_mismatch` | counter | — | `vani:payment:verify` |
| `inventory.reconciliation_mismatch` | counter | `internal` / mã nguồn | `vani:inventory:verify` / `vani:inventory:reconcile` |
| `payments.pending`, `orders.pending` | gauge | mã cổng / — | `vani:metrics:snapshot` (mỗi phút) |
| `integration.outbox_backlog`, `integration.outbox_failed`, `integration.inbox_failed` | gauge | — | như trên |
| `inventory.reconciliation_open`, `payments.reconciliation_open` | gauge | — | như trên (dòng đối soát chưa xử lý) |
| `plugin.active_transactions` | gauge | plugin id | như trên (số việc dở dang theo `guardDisable`) |

Xem tại Admin → Hệ thống → Pulse (quyền `system.monitor`), thẻ "Thương mại" ở đầu dashboard. Thẻ đỏ các counter lỗi khi > 0.

### 4.2 Health check

`GET /health` (throttle 60/phút) gồm các kiểm tra: `database`, `cache`, `required_extensions`, `integration_outbox` (tồn > 1.000 hoặc có lỗi/dead thì `degraded`), `scheduler` (nhịp do `vani:metrics:snapshot` ghi, quá 5 phút thì `degraded`). Kết quả tổng hợp: `ok` / `degraded` → HTTP 200, `fail` → 503 để LB rút node. Chi tiết từng kiểm tra chỉ trả khi header `X-Health-Token` khớp `VANI_HEALTH_TOKEN`. `/up` của Laravel vẫn giữ, chỉ kiểm tra ứng dụng boot được.

### 4.3 Metric mục tiêu (chưa có)

| Nhóm | Metric |
|---|---|
| HTTP | `http_requests_total{route,status}`, `http_request_duration_seconds{route}` |
| Nghiệp vụ | `orders_placed_total{source,payment}`, `checkout_failures_total{reason}`, `payment_success_ratio{gateway}`, `stock_insufficient_total` |
| Inventory | `reservations_active`, `reservations_expired_total`, `inventory_sync_lag_seconds{authority}` |
| Integration | `integration_outbox_pending{target}`, `integration_delivery_seconds{target}`, `integration_dead_total{target}` |
| Queue | `queue_jobs_waiting{queue}`, `queue_job_duration_seconds{job}`, `failed_jobs_total` |
| Extension | `hook_duration_ms{hook,plugin}`, `plugin_failures_total{plugin}` |
| DB | slow query > 200ms, deadlock count, replica lag |

Công cụ: Laravel Pulse (dashboard ứng dụng) + Prometheus/Grafana tự host (hoặc dịch vụ của nhà cung cấp VN). Sentry tự host cho exception.

## 5. Tracing

- OpenTelemetry (PHP SDK) là tuỳ chọn; span cho HTTP request, query DB, job, lời gọi HTTP ra ngoài; `trace_id` = `correlation_id` để nối log với trace.
- Bật sampling 10% ở production, 100% với request lỗi.

## 6. Audit và event log

- `audit_logs` (ai làm gì với dữ liệu nào) ([security](../15-security/security.md)).
- `order_events`, `stock_movements`, `payment_transactions`, `integration_outbox/inbox` là **event log nghiệp vụ**, đủ để dựng lại diễn biến của một đơn.
- Admin có màn hình "Timeline đơn hàng": gộp tất cả các log trên theo `order_id`/`correlation_id`.

## 7. Cảnh báo

### 7.1 Đã có (0.3.30)

`vani:alerts:check` chạy mỗi phút (scheduler, một server), đánh giá các điều kiện dưới đây và báo qua mọi kênh `Shared\Contracts\AlertChannel` đang bật. Core có kênh `mail` (gửi ngay tới `VANI_ALERT_EMAILS`, không qua queue); plugin đóng góp kênh khác (Telegram, Slack, Zalo…) qua `contribute(AlertChannel::TAG, …)`. Lỗi của một kênh được cô lập.

Trạng thái lưu ở `alert_states`: sự cố mới → báo ngay; còn → nhắc lại sau 30 phút (khẩn), 2 giờ (cao), 1 ngày (thường); hết → báo "đã ổn". Không gửi lặp mỗi phút. Cảnh báo đang mở hiện ở ô "Cảnh báo vận hành" trên trang Tổng quan Admin (quyền `system.monitor`). Metric `alerts.fired` (key = mức). `--dry-run` chỉ in, không gửi.

| Mã | Mức | Điều kiện |
|---|---|---|
| `database` | Khẩn | Không truy vấn được DB (chống lặp bằng cache) |
| `extensions.required_missing` | Khẩn | Thiếu extension bắt buộc (thanh toán, giao hàng, thuế…) |
| `integration.order_event_dead` | Khẩn | Có message `order.*` ở trạng thái `dead` |
| `payments.failure_rate` | Khẩn | > 30% khoản thanh toán online thất bại trong 60 phút (tối thiểu 10 khoản; bỏ `cod`, `manual_bank_transfer`) |
| `queue.wait` | Khẩn | Queue Redis chờ > 120s (Horizon) |
| `integration.outbox_backlog` | Cao | Outbox tồn > 500 hoặc message chờ gửi quá 5 phút |
| `integration.dead` | Cao | Message outbox (không phải `order.*`) hoặc inbox ở `dead` |
| `plugins.failed` | Cao | Plugin ở trạng thái `failed` |
| `plugins.health` | Cao | `vani:plugin:health` báo `error` |
| `jobs.failed` | Cao | Có job thất bại trong 60 phút |
| `reconciliation.open` | Thường | Còn dòng đối soát tồn/thanh toán chưa xử lý |

Ngưỡng ở `vanishop.alerts` (`VANI_ALERT_OUTBOX_BACKLOG`, `VANI_ALERT_QUEUE_WAIT`). Scheduler chết thì lệnh này không chạy: dùng giám sát ngoài gọi `GET /health` (mục `scheduler`). Chưa có: tỷ lệ checkout lỗi theo 5 phút, replica lag, deadlock, slow query, gọi điện on-call.

### 7.2 Mục tiêu

| Mức | Điều kiện |
|---|---|
| **Khẩn (on-call)** | Checkout lỗi > 2% trong 5 phút; tỷ lệ thanh toán thành công giảm > 30%; message `order.*` vào `dead`; DB replica lag > 30s; queue `critical` chờ > 1 phút |
| **Cao** | Backlog outbox > 500 hoặc trễ > 5 phút; connector health fail; deadlock tăng đột biến; plugin bị `failed` |
| **Thường** | Lệch tồn > 0,5% sau reconciliation; slow query mới; deprecation của extension point bị gọi |

Kênh cảnh báo: Telegram/Slack + email; khẩn thì gọi điện on-call.

## 8. Kiểm thử

- Test middleware: response luôn có `X-Correlation-Id`; job được dispatch từ request mang cùng id.
- Test outbox: message có `correlation_id` của request tạo ra nó.
- Test log masking: log không chứa SĐT/email thô.
