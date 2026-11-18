# vani.preorder

Đặt trước hàng đang về — plugin tham chiếu cho EP v3 X7 ([ADR-047](../../../docs/19-adr/ADR-047-backorder-incoming-stock.md), [extension-point-catalog](../../../docs/04-extension/extension-point-catalog.md)).

- Trang **Tồn kho** → công cụ "Nhập hàng đang về" (file: SKU, số lượng, ngày dự kiến) → `IncomingStock`; cột "Đang về" (số · ngày về · số đơn chờ hàng).
- `BackorderPolicy`: hạn mức đặt trước = `share_percent`% số đang về. SKU hết hàng thật vẫn bán được tới hạn mức; huỷ đơn tự trả suất.
- Nhập kho (+N) → số đang về giảm N (hạn mức không cộng trùng hàng đã về). Hàng về lấp cho đơn cũ trước.
- Trang sản phẩm: "Đặt trước — dự kiến có hàng dd/mm" theo SKU đang chọn.
- Trang đơn: thẻ "Đặt trước — chờ hàng". Hook `vani.fulfillment.before_push`: đơn còn chờ hàng không đẩy lên hãng được (tắt được).
