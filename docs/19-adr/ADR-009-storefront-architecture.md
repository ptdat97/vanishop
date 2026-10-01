# ADR-009 — Storefront Architecture

- Trạng thái: Accepted · Ngày: 2026-09-28 · **Sửa đổi bởi [ADR-028](ADR-028-single-store-brand-as-catalog.md)**: một giao diện cho cả cửa hàng, không theme/tokens theo brand

## Context
Cần storefront SEO tốt cho nhiều brand, đồng thời hỗ trợ headless/mobile/Zalo Mini App.

## Problem
Nếu native storefront có logic riêng thì hành vi sẽ lệch với API và mỗi brand dễ bị fork giao diện.

## Decision
- Native storefront: **Blade SSR + Alpine**, gọi **cùng Application Query/Command** với Storefront API (in-process, không tự gọi HTTP).
- Headless dùng `/api/storefront/v1`.
- Không business logic trong Blade/Vue/theme/CSS.
- Tuỳ biến brand qua theme tokens, brand config, collection config, content, blocks, override view có chọn lọc (`custom/theme/*`, fallback `vani-base`).

## Alternatives
- SPA/Next.js cho mọi brand: tốt cho headless nhưng thêm hạ tầng Node, SEO/cache phức tạp hơn.
- Native gọi API qua HTTP nội bộ: thêm độ trễ, là dạng distributed monolith.

## Consequences
- (+) Một nguồn logic; headless và native nhất quán; không fork.
- (−) Theme bị ràng buộc vào Blade; brand muốn SPA riêng phải dùng headless.

## Trade-offs
Ưu tiên SEO và đơn giản vận hành hơn trải nghiệm SPA.
