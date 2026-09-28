# 0007 — Hoãn vai trò ODO; xây module Integration cung cấp API tích hợp

- Trạng thái: Accepted
- Ngày: 2026-09-28
- Người quyết định: Owner

## Bối cảnh
Chưa chốt ODO sẽ đảm nhiệm phần nào (chọn kho, tạo vận đơn, đối soát COD, xử lý hàng trả) và tích hợp theo cách nào. Nếu thiết kế cứng quanh ODO, lõi sẽ phải sửa khi phạm vi thay đổi.

## Quyết định
1. **Tạm hoãn** thiết kế chi tiết tích hợp ODO.
2. Xây **module `Integration`** (`modules/Integration`) làm cổng tích hợp chung:
   - **Integration API** `/api/integration/v1` có version, xác thực API key + HMAC, scope theo tài nguyên và theo brand/pháp nhân/location.
   - **Webhook subscription** cho đối tác, gửi qua outbox.
   - **Khung connector** cho dịch vụ bên thứ ba (plugin trong `custom/plugin/`).
   - Outbox/inbox, mapping, bảng quyền sở hữu dữ liệu (`integration_ownerships`), log, dashboard, replay.
3. Trong lúc chưa có ODO, VaniShop **tự fulfillment**: `fulfillment.mode = internal` (sourcing nội bộ + plugin hãng VC). Khi ODO sẵn sàng → cấp Integration Client cho ODO và chuyển brand sang `external`.

Chi tiết: [08](../08-module-integration.md).

## Hệ quả
- (+) Không bị chặn tiến độ bởi quyết định ODO; bất kỳ hệ thống nào (ODO, ERP, POS) đều tích hợp qua cùng một API.
- (+) Lõi không phụ thuộc sản phẩm cụ thể.
- (−) Phải tự xây sourcing và đặt vận đơn trước (vốn có thể do ODO làm) — chấp nhận vì cần cho phương án dự phòng.
- (−) Integration API là hợp đồng công khai: thay đổi phá vỡ cần version mới.
