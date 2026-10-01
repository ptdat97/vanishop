# Thuật ngữ

| Thuật ngữ | Tiếng Việt | Định nghĩa trong VaniShop |
|---|---|---|
| Owner | Chủ sở hữu | Chủ duy nhất của bản cài đặt và dữ liệu khách hàng |
| Store | Cửa hàng | Toàn bộ bản cài đặt: một website, một giao diện, một bộ cấu hình ([store-and-brand](../12-store/store-and-brand.md)) |
| Legal Entity | Pháp nhân vận hành | Công ty có MST đứng tên bán hàng trên website; xuất hoá đơn, nhận tiền. Một bản ghi |
| Brand | Thương hiệu | Nhóm sản phẩm trong Catalog (trang brand, bộ lọc, điều kiện khuyến mãi, báo cáo). **Không** phải phạm vi dữ liệu |
| Order source | Nguồn đơn | `web`, `app`, `zalo`, `admin`, `pos`, `marketplace` — thuộc tính của đơn để báo cáo |
| Channel | Kênh (cũ) | Khái niệm của mô hình đa brand (ADR-008), sẽ gỡ khỏi code ở slice 12. Không nhầm với **kênh gửi tin** (email/SMS/ZNS) của Notification |
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
| Microkernel | Nhân nền tảng | Vòng 0: Extension, Identity, Tenancy, Shared — nạp plugin, registry, hook/event, cấu hình, quyền; không biết thương mại ([commerce-kernel](../02-architecture/commerce-kernel.md)) |
| Plugin hệ thống | Bundled plugin | Plugin đóng gói sẵn, tự bật khi cài, chứa mặc định mang chính sách/đặc thù thị trường (COD, chuyển khoản, phí ship cố định, VAT VN) |
| Extension point bắt buộc | Required extension point | Extension point phải luôn có ≥ 1 (hoặc đúng 1) implementation đang bật; Core chặn tắt implementation cuối |
| Core / Commerce Kernel | Lõi | Phần trong `modules/`: commerce primitives, invariants, extension points ([commerce-kernel](../02-architecture/commerce-kernel.md)) |
| Invariant | Bất biến | Quy tắc nghiệp vụ luôn đúng mà plugin không được phá (không oversell, chuyển trạng thái hợp lệ, tổng không âm…) |
| Snapshot | Ảnh chụp | Bản sao dữ liệu tại thời điểm đặt hàng lưu trong đơn (tên, giá, thuế, địa chỉ…) |
| System of Record (SoR) | Hệ thống lưu gốc | Nơi lưu bản gốc đầy đủ và lâu dài của dữ liệu |
| System of Authority (SoA) | Hệ thống có quyền ghi | Hệ thống duy nhất được ghi một loại dữ liệu trong một scope |
| Correlation ID | Mã truy vết | Mã đi xuyên request → command → event → outbox → hệ thống ngoài |
| Seller | Người bán | Khái niệm của plugin marketplace, không có trong Core |
| Attribution | Ghi nhận nguồn đơn | Liên kết đơn/dòng đơn với creator/campaign (plugin creator) |
| Plugin chính thức | Official plugin | Plugin nghiệp vụ do đội VaniShop phát triển trong `custom/plugin/` ([plugin-catalog](../05-plugin/plugin-catalog.md)) |
| Điểm mở rộng | Extension point | Contract, domain event, hook, registry mà Core cam kết cho plugin; có loại public và internal ([extension-point-catalog](../04-extension/extension-point-catalog.md)) |
| Hook | Điểm mở rộng | Filter/action công khai cho plugin |
| HĐĐT | Hoá đơn điện tử | Hoá đơn theo NĐ 123/2020 (sửa đổi NĐ 70/2025) |
| ZNS | Zalo Notification Service | Tin nhắn giao dịch qua Zalo |
| Clean-room | Phát triển độc lập | Quy trình đảm bảo không sao chép mã của bên thứ ba |
