# ADR-022 — Giỏ khách vãng lai định danh bằng id công khai + token bí mật

- Trạng thái: Accepted · Ngày: 2026-10-03 · Liên quan: [cart-checkout](../03-domains/cart-checkout.md), [api](../06-api/api.md)

## Context
Slice 5 cần giỏ hàng cho Storefront API (headless, mobile, Zalo Mini App) trước khi có module Customer và native storefront. Khách chưa đăng nhập vẫn phải có giỏ riêng, không ai khác đọc/sửa được.

## Problem
Chỉ dùng id của giỏ trong URL thì ai biết id là sửa được giỏ. Dùng cookie phiên thì không hợp với client headless khác domain.

## Decision
- Giỏ có `public_id` (ULID, dùng trong URL) và **token ngẫu nhiên 48 ký tự** trả về **một lần** khi tạo (`meta.token`). Server chỉ lưu `sha256(token)`.
- Mọi request tới giỏ gửi header `X-Vani-Cart-Token`. Token sai, giỏ không tồn tại, hoặc giỏ thuộc kênh khác đều trả cùng một lỗi `404 cart.not_found` (không dò được id hợp lệ).
- Tạo giỏ có rate limit riêng (30/phút/IP).
- Giỏ **không giữ hàng**: chỉ kiểm tra số có thể bán khi thêm/tăng số lượng; giữ hàng xảy ra trong `PlaceOrder` (slice 6).
- Khi có Customer: giỏ gắn `customer_id`; đăng nhập thì gộp giỏ khách vãng lai vào giỏ khách hàng bằng `Carts::merge` (đã có). Native storefront sẽ giữ token trong cookie `HttpOnly`.

## Alternatives
- Session cookie: không dùng được cho client headless khác domain; gắn giỏ với session làm khó gộp giỏ.
- JWT chứa id giỏ: không thu hồi được, không thêm lợi ích so với token ngẫu nhiên + hash.

## Consequences
- (+) Headless, mobile, native dùng chung một cơ chế; lộ id giỏ không đủ để sửa giỏ.
- (−) Client phải tự lưu token; mất token thì mất giỏ (giỏ tự bị dọn sau 30 ngày không hoạt động).
