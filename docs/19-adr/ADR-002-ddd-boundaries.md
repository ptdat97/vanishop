# ADR-002 — DDD Boundaries

- Trạng thái: Accepted · Ngày: 2026-09-28

## Context
Nhiều context (Catalog, Inventory, Ordering…) dùng chung một DB và một process. Nếu không có ranh giới, code sẽ gọi chéo Model và biến thành "big ball of mud".

## Problem
Làm sao giữ ranh giới trong monolith mà không over-engineering?

## Decision
- 21 bounded context ([bounded-contexts](../02-architecture/bounded-contexts.md)), mỗi context sở hữu bảng và quy tắc của mình.
- Lớp trong module: `Contracts` (public), `Events` (public), `Domain`, `Application`, `Persistence`, `Infrastructure`, `Http`, `Policies`, `Tests`.
- `Domain` thuần PHP; `Application` là transaction boundary; `Persistence` chứa Eloquent.
- **Pragmatic**: context "rich domain" (Inventory, Checkout, Ordering, Payment, Pricing, Promotion) và "CRUD domain" (Content, Notification, cấu hình).
- Ranh giới được kiểm tra bằng Pest arch test.

## Alternatives
- Eloquent-everywhere, không tách lớp: nhanh lúc đầu, nhưng invariant rải rác và khó test.
- Full hexagonal cho mọi context: quá nặng với CRUD.

## Consequences
- (+) Invariant tập trung, unit test nhanh, dễ tách service.
- (−) Tốn công mapping record ↔ domain ở các context "rich".

## Trade-offs
Chấp nhận thêm boilerplate ở vài context quan trọng; giữ CRUD đơn giản ở phần còn lại.
