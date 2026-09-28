# ADR-005 — Event-driven Integration

- Trạng thái: Accepted · Ngày: 2026-09-28

## Context
ERP, ODO, cổng thanh toán, hãng VC có thể chậm hoặc lỗi; checkout phải luôn hoạt động.

## Problem
Gọi đồng bộ ra ngoài trong transaction checkout dẫn đến giữ khoá lâu, mất đơn khi lỗi, và trùng lặp khi retry.

## Decision
- Tích hợp **bất đồng bộ**: Domain Event → Outbox (cùng transaction) → Worker → Connector/Webhook → hệ thống ngoài.
- Chiều vào: Webhook/API → Inbox → Application command.
- Retry có backoff, timeout, dead letter, replay, reconciliation ([integration-platform](../11-integration/integration-platform.md)).
- Ngoại lệ duy nhất cho gọi đồng bộ: khởi tạo thanh toán **sau** commit.

## Alternatives
- HTTP đồng bộ trong checkout: đơn giản nhưng mong manh.
- Message broker (Kafka/RabbitMQ) ngay từ đầu: vận hành nặng; outbox + Redis queue là đủ ở quy mô hiện tại.

## Consequences
- (+) Checkout độc lập với hệ thống ngoài; không mất message.
- (−) Nhất quán cuối (trễ vài giây đến vài phút); cần màn hình theo dõi/replay.

## Trade-offs
Đổi tính tức thời lấy độ tin cậy.
