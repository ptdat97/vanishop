# ADR-046 — Vận hành có người ở ba chốt: xác nhận đơn COD, đẩy đơn lên hãng, duyệt đổi trả

- Trạng thái: **Accepted** (tài liệu) · Code: **Planned** — đợt V1–V4 ở [stabilization-2026-11](../20-roadmap/stabilization-2026-11.md#p1--vận-hành-có-người-adr-046) · Ngày: 2026-11-15 · Người quyết định: Owner
- Liên quan: [vision §2.2](../00-overview/vision.md#22-vận-hành-tại-việt-nam-có-người-ở-mỗi-chốt), [ADR-043](ADR-043-fulfillment-stays-in-core.md) (Fulfillment ở Core, gọn cho SME), [order](../09-order/order.md), [fulfillment](../09-order/fulfillment.md), [payment §5](../10-payment/payment.md).

## Context

- Shop thời trang SME tại Việt Nam vận hành bằng người, không bằng quy trình tự động khép kín:
  - **COD là chính, tỷ lệ bom hàng cao.** Đơn COD phải có người gọi/nhắn xác nhận (đúng người, đúng địa chỉ, đúng size) trước khi đóng gói. Tự xác nhận theo luật là rủi ro mất phí giao hai chiều.
  - **Đóng gói và đẩy đơn lên hãng làm theo đợt** (sáng/chiều, trước giờ hãng lấy hàng). Nhân viên lọc đơn đã xác nhận, in phiếu, đóng gói, rồi mới đặt vận đơn. Hàng chưa đóng gói mà đã có vận đơn ở hãng thì shipper đến lấy không có hàng, hoặc hãng tính phí huỷ.
  - **Đổi trả luôn qua CSKH.** Khách nhắn tin/gọi; CSKH xem ảnh, thoả thuận đổi size hay hoàn tiền, quyết định ai chịu phí giao. Không có chính sách đủ chặt để tự duyệt.
- Code hiện tại (Core 0.4.52) tự động ở cả ba chỗ đó: `vani.cod` mặc định **tự xác nhận mọi đơn COD** (`VANI_COD_AUTO_CONFIRM=true`, `auto_confirm_mode`); xác nhận xong là **tự tạo và tự đặt vận đơn** ở hãng có API (`BookShipmentJob` trong `FulfillmentService::createForOrder`); `ReturnDecision::$autoApprove` cho phép chính sách **tự duyệt** đổi trả.
- VaniShop không phải nền tảng TMĐT toàn cầu: không cần luồng "không người" (lights-out) kiểu sàn lớn. Cần luồng ít bước cho người làm, và hệ thống giữ đúng tồn, tiền, trạng thái phía sau.

## Decision

1. **Ba chốt có người, cố định trong Core:**

   | Chốt | Ai làm | Hệ thống làm | Hệ thống **không** làm |
   |---|---|---|---|
   | **Xác nhận đơn COD** | Nhân viên vận hành gọi/nhắn khách, sửa địa chỉ/dòng nếu cần, bấm Xác nhận (từng đơn hoặc hàng loạt) | Giữ hàng ngay khi đặt; hàng đợi "Cần xác nhận"; lịch sử giao/hoàn của SĐT để nhân viên quyết định | Tự xác nhận đơn COD (kể cả "khách quen") |
   | **Đóng gói, đẩy lên hãng** | Nhân viên kho lọc đơn "Chờ đóng gói", in phiếu, đóng gói, bấm **Đẩy lên hãng** (hàng loạt); hãng nhập tay thì nhập mã/nhập file | Tạo vận đơn nháp (`pending_booking`) khi xác nhận để có COD, phiếu, file xuất; hãng API → đặt khi nhân viên bấm; chốt tồn khi hàng rời kho | Tự đặt vận đơn ở hãng ngay khi xác nhận |
   | **Đổi trả** | CSKH duyệt/từ chối, nhận hàng, kiểm tình trạng, hoàn tiền (COD/chuyển khoản: chuyển trả tay) | Khách gửi yêu cầu trên web hoặc CSKH tạo hộ; kiểm số lượng ≤ đã giao, hạn đổi trả; tính tiền hoàn; nhập kho hàng bán được | Tự duyệt yêu cầu đổi trả |

2. **Đơn đã trả tiền trước giữ tự xác nhận** (Owner chọn 2026-11-15): thanh toán online thành công (hoặc nhân viên xác nhận đã nhận chuyển khoản) → đơn `confirmed` như hiện nay. Tiền đã về là đã lọc được phần lớn rủi ro bom hàng; chốt có người tiếp theo là **đẩy lên hãng**.
3. **Không thêm trạng thái "đã đóng gói".** Đóng gói nằm giữa "Chờ đóng gói" (vận đơn `pending_booking`) và "Đã đẩy lên hãng" (vận đơn có mã, `created`). Thêm trạng thái là thêm một việc bấm mỗi đơn mà shop vài người không cần.
4. **Đơn giản logic, không mở rộng thành cấu hình:** gỡ hẳn cấu hình tự xác nhận COD và trường tự duyệt đổi trả, không giữ dưới dạng "tắt sẵn". Shop lớn cần tự động hoá thì viết plugin nghe sự kiện (`OrderPlaced`, `ReturnRequested`) và gọi đúng lệnh của nhân viên (`OrderCommands::confirm`, `ReturnService::approve`) với nguồn `system` — có lịch sử như nhân viên làm.
5. **Bất biến không đổi:** giữ hàng khi đặt, trừ tồn khi rời kho, COD thu khi giao (hoặc nhân viên ghi nhận — 0.4.50), hoàn ≤ đã thu, trả ≤ đã giao. Ba chốt chỉ quyết định **khi nào** lệnh chạy, không đổi lệnh làm gì.

## Alternatives

- **Giữ tự động, tắt sẵn bằng cấu hình** (`auto_confirm_mode = off` mặc định, `autoApprove` giữ trong contract): ít sửa code, nhưng giữ ba nhánh logic + test cho tính năng không ai dùng ở SME, và cấu hình sai một lần là đơn bom hàng đi thẳng ra hãng. Không chọn.
- **Mọi đơn, kể cả đã trả online, đều chờ nhân viên xác nhận:** an toàn nhất nhưng thêm việc cho mỗi đơn online; đơn đã trả tiền vẫn qua chốt đẩy lên hãng nên địa chỉ sai vẫn được bắt khi đóng gói. Owner không chọn.
- **Thêm trạng thái "Đã đóng gói":** tách người đóng gói và người đẩy đơn — hợp với kho lớn, thừa với SME. Để plugin/`vani.warehouse` khi cần.
- **Luồng tự động khép kín của nền tảng TMĐT toàn cầu:** ngoài phạm vi (vision §4).

## Consequences

- (+) Mọi đơn COD qua một người trước khi đóng gói; không có vận đơn ở hãng cho hàng chưa đóng gói; mọi đổi trả qua CSKH — đúng cách shop đang làm.
- (+) Bớt nhánh logic: `AutoConfirmCodOrder`, cài đặt `auto_confirm_mode`/`trusted_min_delivered`, `VANI_COD_AUTO_CONFIRM`, `ReturnDecision::$autoApprove`, dispatch `BookShipmentJob` khi tạo vận đơn.
- (+) Admin phải giỏi hàng đợi + hàng loạt hơn nữa: "Cần xác nhận", "Chờ đóng gói", "Đổi/trả chờ duyệt" là ba trang làm việc chính (G8).
- (−) Đổi contract cho plugin: `ReturnDecision` bỏ `autoApprove`; `vani.cod` bỏ cài đặt; đặt vận đơn qua API chỉ khi nhân viên bấm. Tăng số giữa của Core khi làm (giai đoạn `0.x` — [CHANGELOG-extension](../04-extension/CHANGELOG-extension.md)); dev không cần tương thích ngược.
- (−) Shop muốn tự động phải viết plugin (điểm mở rộng: sự kiện + lệnh của nhân viên).
