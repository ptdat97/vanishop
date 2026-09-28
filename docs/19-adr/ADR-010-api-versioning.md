# ADR-010 — API Versioning

- Trạng thái: Accepted · Ngày: 2026-09-28

## Context
Ba nhóm client: storefront/headless, admin/POS, hệ thống tích hợp.

## Problem
Thay đổi API làm hỏng client ngoài tầm kiểm soát (mobile app đã phát hành, ERP đối tác).

## Decision
- `/api/storefront/v1`, `/api/admin/v1`, `/api/integration/v1`.
- Version trong URL; trong cùng version chỉ thêm (field/endpoint tuỳ chọn). Phá vỡ thì `v2`, chạy song song ≥ 6 tháng, header `Deprecation`/`Sunset`.
- Payload canonical tích hợp có `schema_version` riêng.
- Cursor pagination, filter/sort chuẩn, Idempotency-Key, correlation id, rate limit, scope ([api](../06-api/api.md)).

## Alternatives
- Version qua header/media type: khó debug và cache.
- Không version: không thể phát triển an toàn.

## Consequences
- (+) Client ổn định; nâng cấp có lộ trình.
- (−) Duy trì song song nhiều version tốn công.

## Trade-offs
Chi phí duy trì đổi lấy độ tin cậy với đối tác.
