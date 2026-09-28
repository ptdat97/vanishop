# ADR-014 — Idempotency

- Trạng thái: Accepted · Ngày: 2026-09-28

## Context
Mạng di động chập chờn, cổng thanh toán gửi IPN nhiều lần, worker retry.

## Problem
Request/sự kiện lặp tạo đơn trùng, trừ tồn hai lần, hoàn tiền hai lần.

## Decision
- API tạo tài nguyên nhận `Idempotency-Key`; lưu `idempotency_keys(scope, key, request_hash, response)` 24h; cùng key khác body thì `409`.
- IPN: unique `(gateway, gateway_transaction_id, type)`. Inbox: unique `(system, external_event_id)`. Outbox gửi `Idempotency-Key = message_id`.
- Job/listener thiết kế idempotent (kiểm tra trạng thái trước khi làm).
- Chi tiết: [consistency](../02-architecture/consistency.md).

## Alternatives
- Dựa vào client không bấm hai lần: không an toàn.

## Consequences
- (+) Retry an toàn ở mọi tầng.
- (−) Thêm bảng và kiểm tra ở mỗi endpoint ghi.

## Trade-offs
Chi phí nhỏ, lợi ích lớn.
