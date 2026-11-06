# ADR-043 — Fulfillment ở lại Core; làm gọn cho SME bằng giao diện, phần vận hành nâng cao là plugin

- Trạng thái: **Accepted** · Ngày: 2026-11-07 · Người quyết định: Owner
- Liên quan: [ADR-029](ADR-029-commerce-microkernel.md) (bất biến ở Core), [ADR-042](ADR-042-inventory-warehouse-plugins.md) (mẫu tách Warehouse), [vision §2.1](../00-overview/vision.md#21-định-hướng-giai-đoạn-bắt-đầu-từ-sme-mở-rộng-khi-doanh-nghiệp-lớn-lên) (SME trước, vận hành trước), [fulfillment](../09-order/fulfillment.md).

## Context

- Định hướng SME (vision §2.1): shop nhỏ, một kho, nhân viên tự đẩy đơn lên nhiều hãng vận chuyển; web chỉ ghi mã vận đơn và trạng thái. Câu hỏi: tách module `Fulfillment` thành plugin, hoặc bỏ hẳn (chỉ ghi mã vận đơn trên đơn), cho Core gọn hơn.
- Fulfillment không chỉ là "ghi vận đơn" — nó giữ các điểm mà module khác dựa vào:
  - **chốt hàng đang giữ khi hàng rời kho** (trừ tồn) — thiếu thì hàng giữ không bao giờ trừ, ATS sai dần;
  - `orders.fulfillment_status`, đơn sang `processing` khi tạo vận đơn, `completed` khi hết hạn đổi trả;
  - sự kiện giao theo từng vận đơn → Payment ghi COD đã thu / thu tiền đã giữ;
  - số lượng và ngày đã giao → Returns (trả ≤ đã giao, hạn đổi trả);
  - sự kiện vận đơn → Notification (email), Storefront (tra cứu đơn), Integration (ERP).
- 6 module Core (Ordering, Payment, Returns, Notification, Storefront, Integration) dùng contract/sự kiện của Fulfillment; Core không được phụ thuộc plugin (R4).

## Decision

1. **Fulfillment ở lại Core** (engine vận đơn, sổ `shipment_events`, chốt tồn khi rời kho, trạng thái giao của đơn, hoàn tất sau hạn đổi trả, contract `ShipmentReader`, sự kiện `ShipmentStatusChanged`).
2. **Phần vận hành nâng cao là plugin, đã có điểm mở rộng:** hãng vận chuyển kết nối API (`ShippingCarrier` — vd. `vani.ghn`: tự đặt vận đơn, webhook), giao từ nhiều kho (`SourcingStrategy` + `vani.warehouse`), phí giao (`ShippingRateProvider`), thao tác trạng thái của hãng trên trang đơn (`OrderStatusAction`), cột/thao tác danh sách đơn (slot).
3. **Làm gọn cho SME ở giao diện, không ở module:**
   - Việc giao hàng hằng ngày làm trên danh sách đơn + trang đơn (hàng đợi "Chờ giao hãng", thẻ Giao hàng, xuất/nhập file cho hãng, in phiếu — 0.3.96–0.3.97).
   - Menu Admin "Giao hàng" (trang vận đơn riêng) **chỉ hiện khi cần**: có hãng tự đặt vận đơn qua API, hoặc từ 2 kho đang giao đơn online. Cấu hình `vanishop.fulfillment.admin_menu` (`VANI_FULFILLMENT_MENU` = `auto` | `always` | `never`). Trang vẫn mở được từ link trên trang đơn (0.3.100).
   - Mặc định **một đơn một vận đơn**: chỉ tách nhiều kiện khi có nhiều kho (sourcing theo kho đang giữ hàng).

## Alternatives

- **Tách thành plugin** (như ADR-042): contract và sự kiện vẫn phải ở Core (6 module dùng), chỉ tách được vỏ; tắt plugin làm hỏng nghiệp vụ (không trừ tồn, không ghi COD, không đổi trả được, đơn không hoàn tất) — khác Warehouse, tắt đi shop vẫn bán bình thường. Không chọn.
- **Bỏ hẳn, chỉ ghi mã vận đơn trên đơn**: thực chất là viết lại chốt tồn, ghi COD, kiểm tra đổi trả, email/tra cứu/sự kiện ERP; mất giao nhiều kiện, nhiều kho, hãng API — trái "giữ điểm mở rộng" (vision §2.1); rủi ro cao trên bất biến tồn/tiền. Không chọn.

## Consequences

- (+) Bất biến tồn kho, COD, đổi trả giữ nguyên trong Core; bộ test bất biến không đổi.
- (+) Shop nhỏ không thấy trang vận đơn riêng; doanh nghiệp lớn bật hãng API hoặc `vani.warehouse` là menu tự hiện.
- (−) Core vẫn mang module Fulfillment (~2.400 dòng) kể cả khi shop chỉ nhập mã tay.
