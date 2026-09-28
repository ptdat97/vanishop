# ADR-008 — Multi-brand Model

- Trạng thái: Accepted · Ngày: 2026-09-28

## Context
Một Owner, nhiều pháp nhân, nhiều brand; kênh bán có thể một brand hoặc nhiều brand; khách hàng và tồn dùng chung.

## Problem
Nhầm Brand với Channel/Store/Category làm hỏng mô hình giá, đơn, hoá đơn. Tách DB theo brand thì mất khách hàng hợp nhất.

## Decision
- **Owner → Legal Entity → Brand**; **Channel** là thực thể độc lập chứa 1..N brand; **Location** thuộc pháp nhân, phục vụ 1..N brand.
- Context riêng: Tenancy, Brand, Channel ([multi-brand](../12-multi-brand/multi-brand.md)).
- **Một database**, cô lập bằng `brand_id` + Policy + global scope + `CurrentContext` bắt buộc.
- Product/Cart/Checkout/Order/Payment vẫn thuộc Commerce Core; brand chỉ là phạm vi.

## Alternatives
- DB/schema riêng mỗi brand: khó hợp nhất khách, tồn, báo cáo; migration nhân bản.
- Brand = Channel: không bán được nhiều brand trên một kênh.

## Consequences
- (+) Báo cáo hợp nhất; thêm brand không cần hạ tầng mới.
- (−) Rủi ro rò rỉ dữ liệu nếu quên scope, nên bắt buộc có test cô lập.

## Trade-offs
Cô lập logic (không vật lý) đủ cho một Owner; không phù hợp SaaS nhiều khách hàng (không phải mục tiêu).
