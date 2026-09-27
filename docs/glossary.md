# Thuật ngữ

| Thuật ngữ | Tiếng Việt | Định nghĩa trong VaniShop |
|---|---|---|
| Owner | Chủ sở hữu / Tập đoàn | Chủ duy nhất của bản cài đặt, sở hữu mọi brand và dữ liệu khách hàng |
| Legal Entity | Pháp nhân | Công ty có MST; xuất hoá đơn, nhận tiền |
| Brand | Thương hiệu | Thương hiệu thời trang, thuộc 1 pháp nhân |
| Channel | Kênh bán | Điểm bán cụ thể: website brand, website tập đoàn, sàn, POS, app, social |
| Location | Địa điểm tồn kho | Kho hoặc cửa hàng giữ hàng |
| Style | Mẫu sản phẩm | Sản phẩm hiển thị trên 1 trang chi tiết |
| Style Color | Màu của mẫu | Biến thể màu, có ảnh riêng |
| Variant / SKU | Biến thể | Đơn vị bán nhỏ nhất (màu × size) |
| On-hand | Tồn vật lý | Số lượng thực tế tại location (từ ERP/ODO) |
| Reservation | Giữ hàng | Lượng hàng đang giữ cho giỏ/đơn chưa xuất kho |
| Safety stock | Tồn an toàn | Lượng không bán online |
| ATS | Có thể bán | Available-To-Sell = on_hand − reserved − safety_stock |
| Sourcing | Phân bổ kho | Chọn location xuất hàng cho đơn |
| BOPIS | Mua online nhận tại cửa hàng | Buy Online, Pick-up In Store |
| Ship-from-store | Giao từ cửa hàng | Cửa hàng là điểm xuất đơn online |
| Order Group | Nhóm đơn | Đơn khách đặt 1 lần trên kênh tập đoàn, tách thành nhiều đơn theo brand |
| Adjustment | Điều chỉnh giá | Một dòng cộng/trừ trong totals pipeline (KM, phí, điểm…) |
| Shipment | Kiện giao | Một lần giao hàng của đơn, có mã vận đơn |
| RMA / Return | Đổi trả | Yêu cầu đổi/trả hàng |
| COD | Thu hộ | Thanh toán khi nhận hàng |
| ODO | Hệ thống vận hành đơn/giao nhận | Hệ thống ngoài có thể xử lý pick–pack–ship; vai trò tạm hoãn, sẽ kết nối qua module Integration |
| Integration Client | Đối tác tích hợp | Hệ thống ngoài được cấp API key/scope để gọi Integration API và nhận webhook |
| fulfillment.mode | Chế độ xử lý giao hàng | `internal` (VaniShop tự phân bổ kho, đặt vận đơn) hoặc `external` (hệ thống ngoài xử lý) |
| ERP | Hoạch định nguồn lực | Hệ thống kế toán, hàng hoá, tồn kho gốc |
| Outbox / Inbox | Hộp thư đi / đến | Bảng lưu message tích hợp đảm bảo không mất và không trùng |
| Canonical model | Mô hình chuẩn | Định dạng payload nội bộ có version, độc lập hệ thống ngoài |
| Connector | Bộ kết nối | Plugin trong `custom/plugin/` chuyển đổi và gửi/nhận với một dịch vụ ngoài |
| Core | Lõi | Phần trong `modules/`: kernel nền tảng + nguyên liệu thương mại + điểm mở rộng ([ADR-0009](adr/0009-core-toi-gian-nghiep-vu-bang-plugin.md)) |
| Plugin chính thức | Official plugin | Plugin nghiệp vụ do đội VaniShop phát triển trong `custom/plugin/` ([17](17-danh-muc-plugin.md)) |
| Điểm mở rộng | Extension point | Contract, domain event, hook, registry mà core cam kết cho plugin ([10 §6](10-hook-va-plugin.md)) |
| Hook | Điểm mở rộng | Filter/action công khai cho plugin |
| HĐĐT | Hoá đơn điện tử | Hoá đơn theo NĐ 123/2020 (sửa đổi NĐ 70/2025) |
| ZNS | Zalo Notification Service | Tin nhắn giao dịch qua Zalo |
| Clean-room | Phát triển độc lập | Quy trình đảm bảo không sao chép mã của bên thứ ba |
