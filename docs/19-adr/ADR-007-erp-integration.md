# ADR-007 — ERP Integration (và hoãn vai trò ODO)

- Trạng thái: Accepted · Ngày: 2026-09-28 · Người quyết định: Owner

## Context
Chưa chốt ERP cụ thể; vai trò ODO (chọn kho, vận đơn, đối soát COD, hàng trả) chưa xác định.

## Problem
Thiết kế cứng quanh một ERP/ODO sẽ khiến Core phải sửa khi phạm vi thay đổi.

## Decision
- Abstraction `ErpConnector` (plugin: Odoo, SAP, MISA, custom) **và** Integration API để ERP có đội dev tự tích hợp.
- Phân biệt **System of Record** và **System of Authority**; mỗi loại dữ liệu có đúng một authority theo scope (`integration_ownerships`). Mặc định đồng bộ **một chiều**, không đồng bộ hai chiều ([erp-integration](../11-integration/erp-integration.md)).
- **Hoãn** vai trò ODO; VaniShop tự fulfillment (`fulfillment.mode = internal`). Khi chốt ODO: cấp Integration Client, đổi ownership, chuyển `external`.

## Alternatives
- Chọn và gắn chặt một ERP ngay: rủi ro phải làm lại.
- Đồng bộ hai chiều mọi thứ: xung đột dữ liệu khó giải quyết.

## Consequences
- (+) Không bị chặn tiến độ; đổi ERP chỉ cần connector mới.
- (−) Phải tự xây sourcing và đặt vận đơn trước.

## Trade-offs
Làm thêm phần fulfillment nội bộ để đổi lấy sự độc lập với quyết định ODO.
