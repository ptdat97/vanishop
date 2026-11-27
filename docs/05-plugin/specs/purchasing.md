# Plugin spec: Đơn mua và nhà cung cấp (`vani.purchasing`)

> Trạng thái: **Đề xuất — chưa cần** (Owner hoãn 2026-11-27, [§6b](../../20-roadmap/reference-parity-refactor.md) L41). Ghi lại để làm khi cửa hàng cần; đặc tả chi tiết viết khi bắt đầu, sau khi Owner duyệt.

## Bối cảnh

Core có **phiếu nhập nhanh** (Inventory, `StockReceiptService`): phiếu nháp → "Nhập kho" cộng tồn một lần cho cả phiếu; nhà cung cấp là ô chữ tự do; phiếu đã nhập không sửa. **Hàng đang về** (`IncomingStock`, ADR-047) nhập tay theo SKU hoặc từ file (`vani.preorder`). Giá vốn nhập tay theo SKU (`vani.cost-price`).

Đủ cho cửa hàng nhận hàng một lần, đủ số. Không đủ khi: đặt 100 nhưng nhận nhiều đợt; cần biết đã đặt gì với ai, còn thiếu bao nhiêu; giá nhập mỗi lô khác nhau.

## Phạm vi đề xuất

**Đơn mua nhiều bước**

| Bước | Ý nghĩa | Tác động |
|---|---|---|
| Nháp | Nhà cung cấp, SKU, số lượng, giá nhập, ngày dự kiến | Chưa ảnh hưởng gì |
| Đã đặt | Gửi nhà cung cấp (in, xuất file, gửi thủ công) | Cộng hàng đang về theo SKU → đặt trước, "sắp có hàng" dùng được |
| Nhận một phần (nhiều lần) | Mỗi lần hàng về, quét số đã nhận | Cộng tồn đúng số nhận, trừ hàng đang về, biến động kho mang số đơn mua |
| Đã nhận đủ / Đóng | Đủ, hoặc nhà cung cấp báo không giao phần còn lại | Gỡ phần chưa giao khỏi hàng đang về |
| Huỷ | Trước khi nhận | Gỡ hàng đang về |

Danh sách đơn mua lọc theo trạng thái, nhà cung cấp, quá ngày dự kiến; cảnh báo "đơn mua quá hạn chưa về" trên dashboard.

**Danh bạ nhà cung cấp**: tên, người liên hệ, SĐT, ghi chú, điều khoản (thời gian giao); tuỳ chọn mã SKU của nhà cung cấp và giá nhập gần nhất theo SKU; lịch sử đơn mua (đã đặt, giao đúng hạn).

## Gắn với phần khác

- **Giá vốn bình quân** (L41): nhận hàng có giá nhập → tính lại giá vốn bình quân của SKU. Nên làm liền với plugin này.
- **Gợi ý nhập lại** (Reports): nút "Tạo đơn mua" điền sẵn SKU + số lượng.
- **Đặt trước** (`vani.preorder`): hàng đang về và ngày dự kiến lấy từ đơn mua.

## Extension point dự kiến

| Nhu cầu | Extension point |
|---|---|
| Nhận hàng cộng tồn | Contract nhập kho của Inventory (như phiếu nhập nhanh) — kiểm tra đủ chưa trước khi làm, thiếu thì đề xuất sửa Core |
| Hàng đang về | `IncomingStock` |
| Màn Admin, công cụ nhập file | `adminRoutes`, `adminPages`, `adminTool` |
| Dashboard | slot `vani.admin.dashboard.cards` |

## Ngoài phạm vi

- Công nợ nhà cung cấp (đã trả, còn nợ): nghiêng về kế toán.
- Gửi đơn mua tự động cho nhà cung cấp qua API/Zalo (kết nối ngoài đang đóng băng).

## Câu hỏi cần Owner trả lời khi mở lại

1. Có hay nhận hàng một phần không? Nếu hầu như không, chỉ cần thêm danh bạ nhà cung cấp vào phiếu nhập nhanh.
2. Có cần ghi giá nhập theo đơn mua để tính giá vốn bình quân không?
3. Có cần công nợ nhà cung cấp không?

Khối lượng ước tính: ~3–4 ngày (đơn mua + nhà cung cấp), thêm ~1–2 ngày nếu làm cùng giá vốn bình quân.
