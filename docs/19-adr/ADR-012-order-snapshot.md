# ADR-012 — Order Snapshot

- Trạng thái: Accepted · Ngày: 2026-09-28

## Context
Catalog, giá, khuyến mãi, địa chỉ khách thay đổi theo thời gian; đơn là chứng từ pháp lý/kế toán.

## Problem
Đơn join ngược catalog sẽ hiển thị sai giá/tên khi dữ liệu nguồn đổi hoặc bị xoá.

## Decision
- Order là **bản ghi bất biến**: snapshot tên, SKU, variant, giá, thuế, giảm giá, khuyến mãi, địa chỉ, khách hàng, vận chuyển ([order](../09-order/order.md)).
- Chỉ trạng thái (qua state machine), địa chỉ trước fulfillment, ghi chú, `meta` được thay đổi, và mọi thay đổi đều có `order_events`.
- Thay đổi dòng/giá bằng huỷ một phần, đổi hàng, hoàn tiền.

## Alternatives
- Tham chiếu động tới catalog: gọn dữ liệu nhưng sai về nghiệp vụ.
- Event sourcing toàn phần: quá nặng so với nhu cầu.

## Consequences
- (+) Chứng từ chính xác; hoá đơn, đổi trả, báo cáo đúng lịch sử.
- (−) Dữ liệu trùng lặp; sửa lỗi nhập liệu sau khi đặt phải qua quy trình.

## Trade-offs
Tốn dung lượng để đổi lấy tính đúng đắn.
