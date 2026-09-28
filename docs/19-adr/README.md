# Architecture Decision Records

Mỗi ADR ghi một quyết định quan trọng theo mẫu: **Context · Problem · Decision · Alternatives · Consequences · Trade-offs**. ADR đã `Accepted` thì không sửa nội dung; khi đổi quyết định, tạo ADR mới và đánh dấu ADR cũ `Superseded by ADR-xxx`.

| ADR | Quyết định | Trạng thái |
|---|---|---|
| [001](ADR-001-modular-monolith.md) | Modular Monolith trên Laravel; module tại `modules/` | Accepted |
| [002](ADR-002-ddd-boundaries.md) | DDD boundaries: bounded context, lớp module, public vs internal | Accepted |
| [003](ADR-003-plugin-architecture.md) | Plugin architecture: `custom/plugin/`, manifest, lifecycle; Core tối giản | Accepted |
| [004](ADR-004-extension-points.md) | Extension points: Contract · Event · Hook · Registry + compatibility policy | Accepted |
| [005](ADR-005-event-driven-integration.md) | Tích hợp hướng sự kiện, bất đồng bộ | Accepted |
| [006](ADR-006-inventory-authority.md) | Authority tồn kho: ERP giữ tồn vật lý, VaniShop giữ reservation | Accepted |
| [007](ADR-007-erp-integration.md) | ERP integration qua `ErpConnector` + Integration API; vai trò ODO hoãn | Accepted |
| [008](ADR-008-multi-brand-model.md) | Mô hình Owner → Legal Entity → Brand; Channel độc lập; một DB | Accepted |
| [009](ADR-009-storefront-architecture.md) | Storefront native dùng chung Application layer với API; theme tokens | Accepted |
| [010](ADR-010-api-versioning.md) | Ba nhóm API, version theo URL | Accepted |
| [011](ADR-011-money-representation.md) | Tiền = BIGINT minor unit + currency | Accepted |
| [012](ADR-012-order-snapshot.md) | Order là bản ghi bất biến có snapshot | Accepted |
| [013](ADR-013-outbox-inbox.md) | Transactional Outbox / Inbox | Accepted |
| [014](ADR-014-idempotency.md) | Idempotency cho API, webhook, job, message | Accepted |
| [015](ADR-015-marketplace-architecture.md) | Marketplace là plugin, không biến dạng Core | Accepted |
| [016](ADR-016-mysql.md) | MySQL 8.4 | Accepted |
| [017](ADR-017-admin-ui-inertia.md) | Admin: Inertia + Vue 3 + TypeScript | Accepted |
| [018](ADR-018-infrastructure-vietnam.md) | Hạ tầng và dữ liệu đặt tại Việt Nam | Accepted |
| [019](ADR-019-shared-domain-brand-path.md) | Một domain chung, storefront brand theo đường dẫn | Accepted |
| [020](ADR-020-admin-path-no-2fa.md) | Admin: đường dẫn cấu hình được, không dùng 2FA | Accepted |
| [021](ADR-021-storefront-composition-module.md) | Module Storefront làm tầng ghép (Catalog + Pricing + …) | Accepted |

"Accepted" nghĩa là quyết định đã được chốt, **không** có nghĩa là đã có code. Trạng thái implementation nằm ở [status](../00-overview/status.md).

## Mẫu

```markdown
# ADR-0xx — Tiêu đề
- Trạng thái: Proposed | Accepted | Superseded by ADR-0yy
- Ngày: YYYY-MM-DD · Người quyết định: ...
## Context
## Problem
## Decision
## Alternatives
## Consequences
## Trade-offs
```
