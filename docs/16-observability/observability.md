# Observability

> Trạng thái: **Designed**.

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

- JSON có cấu trúc, mỗi dòng có `timestamp`, `level`, `correlation_id`, `channel_code`, `brand_id`, `actor`, `module`, `plugin` (nếu có), `message`, `context`.
- **Không log PII thô và secret**: SĐT, email, địa chỉ bị che (`09*****123`); token và chữ ký bị loại bỏ.
- Kênh log: `app`, `security` (đăng nhập, sai chữ ký, từ chối quyền), `integration`, `audit` (lưu DB, không phải file), `vani.deprecation`.
- Log tập trung lưu tại VN ([ADR-018](../19-adr/ADR-018-infrastructure-vietnam.md)), giữ 30 ngày nóng + 180 ngày lạnh.

## 4. Metrics

| Nhóm | Metric |
|---|---|
| HTTP | `http_requests_total{route,status}`, `http_request_duration_seconds{route}` |
| Nghiệp vụ | `orders_placed_total{brand,channel,payment}`, `checkout_failures_total{reason}`, `payment_success_ratio{gateway}`, `stock_insufficient_total` |
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
