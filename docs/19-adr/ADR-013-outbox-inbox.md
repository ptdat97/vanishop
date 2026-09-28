# ADR-013 — Transactional Outbox / Inbox

- Trạng thái: Accepted · Ngày: 2026-09-28

## Context
Message tới ERP/đối tác phải đến chắc chắn, không trùng, đúng thứ tự theo aggregate; message vào có thể trùng hoặc sai thứ tự.

## Problem
Dispatch queue sau commit vẫn có thể mất message khi process chết giữa chừng; webhook vào có thể bị gửi lại nhiều lần.

## Decision
- **Outbox**: ghi `integration_outbox` trong cùng transaction nghiệp vụ; worker lấy bằng `FOR UPDATE SKIP LOCKED`; thứ tự theo aggregate; backoff; `dead`; replay.
- **Inbox**: lưu trước khi xử lý, unique `(system, external_event_id)`, trả 2xx ngay, xử lý bất đồng bộ; chặn bản cũ bằng version.

## Alternatives
- Chỉ dùng queue: có thể mất message.
- CDC (Debezium) đọc binlog: hạ tầng nặng.

## Consequences
- (+) At-least-once delivery + idempotent consumer, tương đương effectively-once.
- (−) Thêm bảng, worker, màn hình vận hành.

## Trade-offs
Độ phức tạp vận hành vừa phải để đổi lấy độ tin cậy.
