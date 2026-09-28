# ADR-015 — Marketplace Architecture

- Trạng thái: Accepted · Ngày: 2026-09-28

## Context
Có thể cần bán hàng ký gửi/đối tác hoặc settlement giữa các brand.

## Problem
Thêm `seller_id`, commission vào Core sẽ làm biến dạng Product/Order/Payment cho mọi trường hợp không cần marketplace.

## Decision
- Marketplace là **plugin** `vani.marketplace`: Seller, Seller Product/SKU, Commission, Seller Order (hình chiếu dòng đơn), Settlement, Payout ([marketplace](../13-marketplace/marketplace.md)).
- Core vẫn quản lý Product, Cart, Checkout, Order, Payment, Inventory, Fulfillment; plugin mở rộng qua `SourcingStrategy`, hook `vani.order.after_create`, events, location `virtual`, `PromotionRule`.
- Creator/Affiliate theo cùng nguyên tắc ([creator-affiliate](../13-marketplace/creator-affiliate.md)).

## Alternatives
- Marketplace trong Core: phức tạp cho mọi brand.
- Hệ thống marketplace riêng: trùng lặp checkout/đơn.

## Consequences
- (+) Core sạch; bật marketplace khi cần.
- (−) Báo cáo theo seller phải join bảng plugin.

## Trade-offs
Một ít phức tạp khi truy vấn để đổi lấy Core ổn định.
