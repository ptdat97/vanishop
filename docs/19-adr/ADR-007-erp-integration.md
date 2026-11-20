# ADR-007 — ERP Integration (và hoãn vai trò ODO)

- Trạng thái: Accepted · Ngày: 2026-09-28 · Người quyết định: Owner · **Sửa đổi 2026-11-20**: phần ERP đóng băng (mục cuối)

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

## Sửa đổi 2026-11-20 (Owner, rà soát kiến trúc đề xuất 8): đóng băng phần ERP

- Kết nối ngoài đóng băng (chưa có ERP, chưa có đối tác). Module `Integration` (event feed, outbox/inbox, webhook ký HMAC, Integration API) và hợp đồng `Integration\Contracts\Connector`, `InboundHandler` **giữ nguyên code + test**, nhưng **không mở rộng** và **không bảo đảm ổn định**: được đổi/xoá khi Core cần mà không cần deprecation (giai đoạn `dev`, [ADR-048](ADR-048-extension-api-version.md)).
- Mở lại khi Owner chọn ERP cụ thể: khi đó chốt lại hợp đồng connector cùng ERP đó.

