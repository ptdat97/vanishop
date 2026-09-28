# ADR-006 — Inventory Authority

- Trạng thái: Accepted · Ngày: 2026-09-28

## Context
Tồn vật lý có thể do ERP/POS/ODO quản lý, nhưng checkout online cần quyết định ngay có bán được hay không.

## Problem
Nếu ERP ghi thẳng tồn khả dụng, reservation của đơn online có thể bị phá, dẫn đến oversell.

## Decision
- **Reservation và ATS là invariant của Core**, authority luôn là VaniShop.
- **On-hand** có authority cấu hình **theo location** (mặc định VaniShop; có thể là ERP/POS/ODO).
- Authority ngoài chỉ gửi **số tuyệt đối có version**; không bao giờ chạm `reserved`.
- Mọi biến động là movement trong ledger append-only; reserve atomic bằng khoá dòng.
- `InventoryStrategy` là extension nhưng chỉ được **giảm** ATS.

## Alternatives
- ERP là authority cả availability: checkout phụ thuộc ERP realtime.
- Trừ tồn trực tiếp trên SKU: không truy vết, dễ race condition.

## Consequences
- (+) Không oversell; truy vết được mọi biến động; chịu được ERP chậm.
- (−) Có thể có "available âm" khi ERP hạ on-hand, nên cần cảnh báo và quy trình xử lý.

## Trade-offs
Chấp nhận trạng thái trung gian cần xử lý tay trong hiếm trường hợp, đổi lấy checkout nhanh và an toàn.
