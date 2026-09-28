# ADR-016 — MySQL 8.4

- Trạng thái: Accepted · Ngày: 2026-09-28 · Người quyết định: Owner

## Context
Cần khoá dòng, `SKIP LOCKED`, JSON, được hỗ trợ tốt bởi nhà cung cấp tại VN và đội quen thuộc.

## Problem
Chọn engine cho production.

## Decision
MySQL 8.4 LTS, InnoDB, `utf8mb4_0900_ai_ci`, `READ COMMITTED`. SQLite chỉ cho unit test. Bù trừ thiếu hụt so với PostgreSQL: index ghép thay partial index, generated column cho JSON, `search_text` thay `unaccent`, `number_sequences` thay sequence, migration idempotent vì DDL không transactional ([database](../07-database/database.md)).

## Alternatives
PostgreSQL 16 (partial index, unaccent, DDL transactional). Owner chọn MySQL.

## Consequences
- (+) Phổ biến, managed service sẵn ở VN.
- (−) Không có FK trên bảng partition; cẩn trọng deadlock (khoá theo thứ tự).

## Trade-offs
Một số tiện ích kém hơn PostgreSQL để đổi lấy vận hành quen thuộc.
