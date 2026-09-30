# ADR-024 — Phiên khách hàng trên Storefront API: token Bearer mờ, không dùng Sanctum

- Trạng thái: Accepted · Ngày: 2026-10-12 · Liên quan: [customer](../03-domains/customer.md), [security](../15-security/security.md), [ADR-022](ADR-022-guest-cart-token.md)

## Context
Module Customer cần phiên đăng nhập cho Storefront API (headless, mobile, Zalo Mini App). Thiết kế ban đầu ghi "mobile/headless dùng Sanctum token", nhưng `laravel/sanctum` chưa có trong dependency và R25 cấm thêm dependency chưa được duyệt.

## Problem
Cần token thu hồi được, có hạn, không lưu dạng rõ, dùng chung cho mọi client, mà không kéo thêm package và không trộn với guard `staff` của Admin.

## Decision
- Đăng nhập (OTP hoặc mật khẩu) trả **token ngẫu nhiên 64 ký tự** trong `meta.token` — chỉ trả một lần. Server chỉ lưu `sha256(token)` trong `customer_tokens` (kèm `expires_at`, mặc định 90 ngày, `last_used_at`).
- Client gửi `Authorization: Bearer <token>`. Middleware `vani.customer` (bắt buộc) / `vani.customer:optional` (giỏ, checkout) chạy sau `vani.api-channel`, đặt actor `customer` vào `CurrentContext` và giữ kênh/brand.
- Token sai/hết hạn/khách không còn `active` → `401 customer.unauthenticated` (kể cả ở chế độ optional, để client biết phải đăng nhập lại).
- Đăng xuất xoá token; hợp nhất khách hoặc ẩn danh hoá xoá mọi token của khách.
- Giỏ của khách (có `customer_id`) truy cập bằng phiên, không cần `X-Vani-Cart-Token`; khi giỏ vãng lai được gắn vào khách, token giỏ cũ bị vô hiệu.

## Alternatives
- Sanctum personal access token: gần như cùng cơ chế, nhưng thêm dependency cần duyệt; có thể chuyển sau mà không đổi API (vẫn Bearer).
- Session cookie: không hợp client headless khác domain; native storefront sau này có thể đặt token vào cookie `HttpOnly`.
- JWT: không thu hồi được nếu không có blacklist.

## Consequences
- (+) Không thêm dependency; một cơ chế cho mọi client; thu hồi tức thì.
- (−) Tự bảo trì bảng token và dọn token hết hạn (chưa có lệnh prune).

## Trade-offs
Tự viết ~50 dòng thay vì thêm package; hành vi tương đương personal access token.
