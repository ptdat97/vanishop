# ADR-046 — Vận hành có người ở ba chốt: xác nhận đơn COD, đẩy đơn lên hãng, duyệt đổi trả

- Trạng thái: **Accepted** · Code: **Implemented** (Core 0.4.53–0.4.54, 2026-11-15) — đợt V1–V4 (kế hoạch stabilization-2026-11 đã xong và gỡ — xem lịch sử git; kiểm chứng: `tests/Feature/Operations/StaffedCheckpointsTest`) · Ngày: 2026-11-15 (bổ sung cùng ngày: giữ luồng tự động làm điểm mở rộng) · Người quyết định: Owner
- Liên quan: [vision §2.2](../00-overview/vision.md#22-vận-hành-tại-việt-nam-có-người-ở-mỗi-chốt), [ADR-043](ADR-043-fulfillment-stays-in-core.md) (Fulfillment ở Core, gọn cho SME), [extension-model](../04-extension/extension-model.md), [order](../09-order/order.md), [fulfillment](../09-order/fulfillment.md), [payment §5](../10-payment/payment.md).

## Context

- Shop thời trang SME tại Việt Nam vận hành bằng người, không bằng quy trình tự động khép kín:
  - **COD là chính, tỷ lệ bom hàng cao.** Đơn COD phải có người gọi/nhắn xác nhận (đúng người, đúng địa chỉ, đúng size) trước khi đóng gói. Tự xác nhận theo luật là rủi ro mất phí giao hai chiều.
  - **Đóng gói và đẩy đơn lên hãng làm theo đợt** (sáng/chiều, trước giờ hãng lấy hàng). Nhân viên lọc đơn đã xác nhận, in phiếu, đóng gói, rồi mới đặt vận đơn. Hàng chưa đóng gói mà đã có vận đơn ở hãng thì shipper đến lấy không có hàng, hoặc hãng tính phí huỷ.
  - **Đổi trả luôn qua CSKH.** Khách nhắn tin/gọi; CSKH xem ảnh, thoả thuận đổi size hay hoàn tiền, quyết định ai chịu phí giao. Không có chính sách đủ chặt để tự duyệt.
- Core đã có sẵn các luồng tự động chuẩn, có test: `vani.cod` tự xác nhận COD (`auto_confirm_mode`: mọi đơn / khách quen / tắt — **mặc định đang bật**), Fulfillment tự đặt vận đơn ở hãng có API ngay khi tạo (`BookShipmentJob` trong `FulfillmentService::createForOrder`), `ReturnDecision::$autoApprove` cho chính sách tự duyệt đổi trả (chính sách mặc định `days_window` không dùng).
- Khi shop lớn lên (nhiều đơn, tỷ lệ bom hàng thấp, kho có quy trình), chính các luồng tự động đó sẽ cần lại — từng phần, theo điều kiện riêng của shop.
- VaniShop không phải nền tảng TMĐT toàn cầu: không xây luồng "không người" làm mặc định.

## Decision

1. **Ba chốt có người là mặc định:**

   | Chốt | Ai làm | Hệ thống làm | Mặc định hệ thống **không** làm |
   |---|---|---|---|
   | **Xác nhận đơn COD** | Nhân viên vận hành gọi/nhắn khách, sửa địa chỉ/dòng nếu cần, bấm Xác nhận (từng đơn hoặc hàng loạt) | Giữ hàng ngay khi đặt; hàng đợi "Cần xác nhận"; lịch sử giao/hoàn của SĐT để nhân viên quyết định | Tự xác nhận đơn COD |
   | **Đóng gói, đẩy lên hãng** | Nhân viên kho lọc đơn "Chờ đóng gói", in phiếu, đóng gói, bấm **Đẩy lên hãng** (hàng loạt); hãng nhập tay thì nhập mã/nhập file | Tạo vận đơn nháp (`pending_booking`) khi xác nhận để có COD, phiếu, file xuất; hãng API → đặt khi nhân viên bấm; chốt tồn khi hàng rời kho | Tự đặt vận đơn ở hãng ngay khi xác nhận |
   | **Đổi trả** | CSKH duyệt/từ chối, nhận hàng, kiểm tình trạng, hoàn tiền (COD/chuyển khoản: chuyển trả tay) | Khách gửi yêu cầu trên web hoặc CSKH tạo hộ; kiểm số lượng ≤ đã giao, hạn đổi trả; tính tiền hoàn; nhập kho hàng bán được | Tự duyệt yêu cầu đổi trả |

2. **Đơn đã trả tiền trước giữ tự xác nhận** (Owner chọn 2026-11-15): thanh toán online thành công (hoặc nhân viên xác nhận đã nhận chuyển khoản) → đơn `confirmed` như hiện nay. Chốt có người tiếp theo là **đẩy lên hãng**.
3. **Không thêm trạng thái "đã đóng gói".** Đóng gói nằm giữa "Chờ đóng gói" (vận đơn `pending_booking`) và "Đã đẩy lên hãng" (vận đơn có mã, `created`).
4. **Không gỡ luồng tự động đã chuẩn — giữ làm điểm mở rộng, đổi mặc định sang có người** (Owner bổ sung 2026-11-15). Mỗi chốt có một điểm bật tự động trong Core và một cách cho plugin can thiệp:

   | Chốt | Bật tự động (cấu hình, mặc định có người) | Plugin can thiệp | Ràng buộc chung |
   |---|---|---|---|
   | ① Xác nhận COD | `vani.cod` → `auto_confirm_mode` = `off` (mặc định) · `trusted` · `all`; `VANI_COD_AUTO_CONFIRM` mặc định `false` | Nghe `OrderPlaced`, tự quyết (chấm điểm, danh sách đen SĐT, giờ làm việc…) rồi gọi `OrderCommands::confirm` hoặc để nguyên chờ nhân viên; slot trang đơn/danh sách đơn để hiện gợi ý cho nhân viên | Lệnh như nhân viên, nguồn `system`, ghi `order_events` (lý do `auto_confirm:<plugin>`) |
   | ② Đẩy lên hãng | `core.fulfillment.booking` (Admin → Cài đặt → Kho & giao hàng từ 0.5.53; mặc định `VANI_FULFILLMENT_BOOKING`) = `manual` (mặc định) · `on_create` (đặt ngay khi tạo vận đơn — hành vi hiện tại); hãng phải có `autoBooking` | Nghe `ShipmentCreated` hoặc lịch riêng (vd. đặt theo đợt 10h/15h), gọi lệnh "Đẩy lên hãng" của Fulfillment; plugin hãng (`ShippingCarrier`) quyết cách đặt | Đặt ở hãng luôn sau commit, idempotent theo vận đơn; lỗi → `booking_failed` vào hàng đợi sự cố |
   | ③ Duyệt đổi trả | Chính sách mặc định `days_window` không tự duyệt | `ReturnPolicy` của plugin trả `ReturnDecision::$autoApprove = true` khi đủ điều kiện (vd. đổi size cùng mẫu, khách quen); nghe `ReturnRequested` để gợi ý/gán CSKH | Duyệt tự động vẫn qua `ReturnService` (nguồn `system`, lý do `auto_approved`); nhận hàng, hoàn tiền thủ công vẫn là CSKH |

   Quy tắc cho mọi điểm mở rộng: **tự động hoá chỉ được gọi đúng lệnh của nhân viên** (cùng kiểm tra trạng thái, khoá, bất biến, lịch sử); không ghi thẳng trạng thái, không có đường tắt riêng cho máy.
5. **Bất biến không đổi:** giữ hàng khi đặt, trừ tồn khi rời kho, COD thu khi giao (hoặc nhân viên ghi nhận — 0.4.50), hoàn ≤ đã thu, trả ≤ đã giao. Ba chốt chỉ quyết định **khi nào** lệnh chạy và **ai** bấm, không đổi lệnh làm gì.

## Alternatives

- **Gỡ hẳn các nhánh tự động** (bản đầu của ADR này): logic gọn hơn, nhưng mất luồng đã chuẩn và có test; khi shop lớn lên phải viết lại. Owner không chọn.
- **Giữ tự động làm mặc định như hiện nay:** trái cách shop SME vận hành; đơn bom hàng đi thẳng ra hãng. Không chọn.
- **Mọi đơn, kể cả đã trả online, đều chờ nhân viên xác nhận:** an toàn nhất nhưng thêm việc cho mỗi đơn online; đơn đã trả vẫn qua chốt đẩy lên hãng. Owner không chọn.
- **Thêm trạng thái "Đã đóng gói":** hợp với kho lớn, thừa với SME. Để plugin/`vani.warehouse` khi cần.

## Consequences

- (+) Mặc định: mọi đơn COD qua một người trước khi đóng gói; không có vận đơn ở hãng cho hàng chưa đóng gói; mọi đổi trả qua CSKH — đúng cách shop đang làm.
- (+) Khi lớn lên: bật lại từng chốt bằng cấu hình hoặc plugin, không sửa Core; tự động và người làm đi chung một lệnh, chung lịch sử.
- (+) Admin phải giỏi hàng đợi + hàng loạt: "Cần xác nhận", "Chờ đóng gói", "Đổi/trả chờ duyệt" là ba trang làm việc chính (G8).
- (−) Core giữ ba nhánh tự động + test (thay vì gỡ). Mỗi nhánh phải có test cho cả mặc định (có người) và khi bật.
- (−) Đổi mặc định là thay đổi hành vi với shop đang chạy `auto_confirm_mode` để trống (đang hiểu là bật) — ghi CHANGELOG; dev không cần tương thích ngược.
