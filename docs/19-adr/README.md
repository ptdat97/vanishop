# Architecture Decision Records

Mỗi ADR ghi một quyết định quan trọng theo mẫu: **Context · Problem · Decision · Alternatives · Consequences · Trade-offs**, thêm mục tuỳ chọn **Bài học thực tế** (sự cố/cạm bẫy đã gặp thật và nguyên tắc rút ra). ADR đã `Accepted` thì không sửa nội dung; khi đổi quyết định, tạo ADR mới và đánh dấu ADR cũ `Superseded by ADR-xxx`.

| ADR | Quyết định | Trạng thái |
|---|---|---|
| [001](ADR-001-modular-monolith.md) | Modular Monolith trên Laravel; module tại `modules/` | Accepted |
| [002](ADR-002-ddd-boundaries.md) | DDD boundaries: bounded context, lớp module, public vs internal | Accepted |
| [003](ADR-003-plugin-architecture.md) | Plugin architecture: `custom/plugin/`, manifest, lifecycle; Core tối giản | Accepted |
| [004](ADR-004-extension-points.md) | Extension points: Contract · Event · Hook · Registry + compatibility policy | Accepted |
| [005](ADR-005-event-driven-integration.md) | Tích hợp hướng sự kiện, bất đồng bộ | Accepted |
| [006](ADR-006-inventory-authority.md) | Authority tồn kho: ERP giữ tồn vật lý, VaniShop giữ reservation | Accepted |
| [007](ADR-007-erp-integration.md) | ERP integration qua `ErpConnector` + Integration API; vai trò ODO hoãn | Accepted |
| [008](ADR-008-multi-brand-model.md) | Mô hình Owner → Legal Entity → Brand; Channel độc lập; một DB | Superseded by 028 |
| [009](ADR-009-storefront-architecture.md) | Storefront native dùng chung Application layer với API; theme tokens | Accepted (sửa đổi bởi 028) |
| [010](ADR-010-api-versioning.md) | Ba nhóm API, version theo URL | Accepted |
| [011](ADR-011-money-representation.md) | Tiền = BIGINT minor unit + currency | Accepted |
| [012](ADR-012-order-snapshot.md) | Order là bản ghi bất biến có snapshot | Accepted |
| [013](ADR-013-outbox-inbox.md) | Transactional Outbox / Inbox | Accepted |
| [014](ADR-014-idempotency.md) | Idempotency cho API, webhook, job, message | Accepted |
| [015](ADR-015-marketplace-architecture.md) | Marketplace là plugin, không biến dạng Core | Accepted |
| [016](ADR-016-mysql.md) | MySQL 8.4 | Accepted |
| [017](ADR-017-admin-ui-inertia.md) | Admin: Inertia + Vue 3 + TypeScript | Accepted |
| [018](ADR-018-infrastructure-vietnam.md) | Hạ tầng và dữ liệu đặt tại Việt Nam | Accepted |
| [019](ADR-019-shared-domain-brand-path.md) | Một domain chung, storefront brand theo đường dẫn | Superseded by 028 |
| [020](ADR-020-admin-path-no-2fa.md) | Admin: đường dẫn cấu hình được, không dùng 2FA | Accepted |
| [021](ADR-021-storefront-composition-module.md) | Module Storefront làm tầng ghép (Catalog + Pricing + …) | Accepted |
| [022](ADR-022-guest-cart-token.md) | Giỏ khách vãng lai: id công khai + token bí mật, giỏ không giữ hàng | Accepted |
| [023](ADR-023-stateless-checkout.md) | Checkout không lưu phiên; PlaceOrder một transaction; một brand mỗi đơn | Accepted (sửa đổi bởi 028) |
| [024](ADR-024-customer-api-token.md) | Phiên khách trên Storefront API: token Bearer mờ (hash), không dùng Sanctum | Accepted |
| [025](ADR-025-native-storefront-ssr-slots.md) | Native storefront: SSR-first, JS tăng cường cục bộ, slot UI chỉ nối thêm, thay khối bằng override theme | Accepted |
| [026](ADR-026-plugin-deploy-via-code.md) | Plugin chỉ vào hệ thống qua mã nguồn + CI, không upload qua Admin | Accepted |
| [027](ADR-027-plugin-data-no-core-columns.md) | Plugin mở rộng dữ liệu bằng bảng `plg_*` + `meta`, không thêm cột vào bảng Core | Accepted |
| [028](ADR-028-single-store-brand-as-catalog.md) | **Một Owner, một website, một giao diện; brand là thuộc tính catalog** | Accepted |
| [029](ADR-029-commerce-microkernel.md) | **Microkernel thương mại: bốn vòng, nghiệp vụ là plugin, mặc định chính sách là plugin hệ thống** | Accepted |

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
## Bài học thực tế (tuỳ chọn)
```
