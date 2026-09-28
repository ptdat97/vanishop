# ADR-023 — Checkout không lưu phiên; PlaceOrder một transaction, một brand mỗi đơn

- Trạng thái: Accepted · Ngày: 2026-10-04 · Liên quan: [cart-checkout](../03-domains/cart-checkout.md), [ADR-014](ADR-014-idempotency.md), [ADR-022](ADR-022-guest-cart-token.md)

## Context
Slice 6 cần xem tổng tiền (có voucher, phí giao, VAT) và đặt hàng qua Storefront API, trước khi có module Customer, Payment gateway và order group.

## Problem
- Lưu voucher/địa chỉ vào giỏ hay một bảng "phiên checkout" làm Cart phụ thuộc Promotion và thêm trạng thái phải đồng bộ.
- Khách có thể thấy một tổng tiền rồi bị tính tổng khác khi đặt (giá, khuyến mãi, phí vừa đổi).
- Kênh đa brand cần tách đơn theo brand/pháp nhân (order group) — chưa có.

## Decision
- **Không có phiên checkout.** Client gửi đủ dữ liệu (voucher, địa chỉ, phương thức) mỗi lần: `POST /checkout/{cart}/quote` (chỉ tính, không ghi gì) và `POST /checkout/{cart}/orders`. Thay cho `POST/DELETE /carts/{id}/vouchers` trong thiết kế cũ.
- Đặt hàng **bắt buộc** `Idempotency-Key` và `expected_total` (tổng khách đã thấy). Tổng tính lại trong transaction khác `expected_total` → `409 checkout.totals_changed` kèm tổng mới.
- Một transaction: khoá giỏ (`FOR UPDATE`) → totals pipeline → validator (+ hook) → giữ hàng → tạo đơn (snapshot, số đơn) → ghi lượt khuyến mãi (UPDATE có điều kiện) → hook `vani.order.after_create` → đóng giỏ → lưu phản hồi idempotency. Lỗi ở bất kỳ bước nào → rollback toàn bộ và nhả Idempotency-Key.
- Mã voucher không áp được khi đặt hàng → `422 checkout.voucher_invalid` (không âm thầm bỏ voucher: khách đã thấy giá có giảm).
- **Một brand mỗi đơn** cho tới khi có order group (`422 checkout.invalid`, issue `multi_brand`). Kênh hiện tại là kênh theo brand ([ADR-019](ADR-019-shared-domain-brand-path.md)) nên không ảnh hưởng.
- COD: giữ hàng không hết hạn (nhả khi huỷ hoặc xuất kho). Thanh toán online: giữ theo TTL (slice Payment).

## Alternatives
- Bảng `checkout_sessions`: tiện cho native storefront nhiều bước, nhưng thêm trạng thái và dọn dẹp. Có thể thêm sau ở tầng Storefront mà không đổi contract `Checkout`.
- Không bắt `expected_total`: đơn có thể mang tổng khác lúc khách xác nhận — trái yêu cầu hiển thị giá rõ ràng trước khi xác nhận (NĐ 52/2013).

## Consequences
- (+) Cart không phụ thuộc Promotion; quote an toàn để gọi nhiều lần; đặt hàng idempotent và nguyên tử.
- (−) Client phải giữ voucher/địa chỉ phía mình giữa các bước.
