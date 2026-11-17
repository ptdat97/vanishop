# Thuật ngữ

| Thuật ngữ | Tiếng Việt | Định nghĩa trong VaniShop |
|---|---|---|
| Owner | Chủ sở hữu | Chủ duy nhất của bản cài đặt và dữ liệu khách hàng |
| Store | Cửa hàng | Toàn bộ bản cài đặt: một website, một giao diện, một bộ cấu hình ([store-and-brand](../12-store/store-and-brand.md)) |
| Legal Entity | Pháp nhân vận hành | Công ty có MST đứng tên bán hàng trên website; xuất hoá đơn, nhận tiền. Một bản ghi |
| Brand | Thương hiệu | Nhóm sản phẩm trong Catalog (trang brand, bộ lọc, điều kiện khuyến mãi, báo cáo). **Không** phải phạm vi dữ liệu |
| Order source | Nguồn đơn | `web`, `app`, `zalo`, `admin`, `pos` — thuộc tính của đơn để báo cáo |
| Channel | Kênh bán (khái niệm cũ) | Khái niệm của mô hình đa brand (ADR-008), đã gỡ khỏi code ở slice 12 theo ADR-028. Không nhầm với **kênh gửi tin** (email/SMS/ZNS) của Notification |
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
| Xác nhận đơn | Chốt ① | Nhân viên gọi/nhắn khách xác nhận đơn COD trước khi đóng gói ([ADR-046](../19-adr/ADR-046-staffed-operations-vietnam.md)); đơn `pending` → `confirmed` |
| Chờ đóng gói | Hàng đợi chốt ② | Đơn đã xác nhận, vận đơn nháp `pending_booking`, chưa có ở hãng |
| Đẩy lên hãng | Chốt ② | Nhân viên đặt vận đơn ở hãng (bấm cho hãng API, hoặc nhập mã/nhập file cho hãng nhập tay) sau khi đóng gói; vận đơn `created` |
| Bàn giao | — | Shipper lấy hàng; vận đơn `picked_up`, tồn được trừ |
| Bom hàng | — | Khách COD không nhận hàng; vận đơn hoàn về (`returned`), hàng nhập lại kho |
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
| Plugin chính thức | Official plugin | Plugin nghiệp vụ do đội VaniShop phát triển trong `custom/plugin/` ([plugin-catalog](../05-plugin/plugin-catalog.md)) |
| Điểm mở rộng | Extension point | Contract, domain event, hook, registry mà Core cam kết cho plugin; có loại public và internal ([extension-point-catalog](../04-extension/extension-point-catalog.md)) |
| Hook | Điểm mở rộng | Filter/action công khai cho plugin |
| HĐĐT | Hoá đơn điện tử | Hoá đơn theo NĐ 123/2020 (sửa đổi NĐ 70/2025) |
| ZNS | Zalo Notification Service | Tin nhắn giao dịch qua Zalo |
| Clean-room | Phát triển độc lập | Quy trình đảm bảo không sao chép mã của bên thứ ba |
| Chờ xác nhận | Cần xác nhận | Đơn COD vừa đặt, chờ nhân viên gọi/nhắn khách rồi bấm Xác nhận ([ADR-046](../19-adr/ADR-046-staffed-operations-vietnam.md)) |
| Chờ đóng gói | Vận đơn chưa đặt (`pending_booking`) | Đơn đã xác nhận, đã có vận đơn nháp (COD, phiếu in) nhưng chưa đặt với hãng |
| Đẩy lên hãng | Đặt vận đơn với hãng | Nhân viên kho đóng gói xong mới bấm; vận đơn có mã (`created`). Plugin chặn được qua hook `vani.fulfillment.before_push` |
| Tín hiệu rủi ro | Risk signal | Cảnh báo trên đơn chờ xác nhận (SĐT danh sách đen, từng hoàn về…) do plugin `OrderRiskSignal` chấm; mức thấp/trung bình/cao |
| Đặt trước | Backorder có giới hạn | Bán SKU vượt tồn thật tối đa hạn mức của `BackorderPolicy`, chỉ ở kho bù tồn ([ADR-047](../19-adr/ADR-047-backorder-incoming-stock.md)) |
| Chờ hàng | Số đang chờ hàng | `max(0, reserved + safety_stock − on_hand)` ở kho bù tồn; hàng về lấp đơn giữ trước |
| Hàng đang về | Incoming stock | Số lượng + ngày dự kiến theo SKU (`IncomingStock`); không tự cộng vào ATS |
| Đối soát lệch | Reconciliation mismatch | Tiền cổng/hãng/sao kê báo về khác khoản thanh toán: không ghi nhận, vào hàng đợi cho nhân viên xử lý |
| Chuyển trả | Refund payout | Chuyển khoản hoàn cho khách (nhân viên làm tay hoặc kênh của plugin `RefundPayout`); khoản hoàn `requested` → `paying_out` → `completed` |
| Ghi chú CSKH | Customer note | Ghi chú theo khách của nhân viên hoặc plugin (nguồn `staff` / `system:<plugin>`), hiện ở trang khách và trang đơn |
| API cho plugin | Plugin API | Class có `#[PluginApi]` + hook + `PluginServiceProvider`; version riêng `vanishop.extension_api` ([ADR-048](../19-adr/ADR-048-extension-api-version.md)). Phần còn lại của `Contracts`/`Events` là **API giữa module** |
| Experimental | Thử nghiệm | Mức ổn định của điểm mở rộng mới: đổi được ở bản minor của API cho plugin; lên `stable` sau một vòng có plugin thật dùng |
| Kho bù tồn | Top-up location | Kho giao online đầu tiên (theo ưu tiên) do VaniShop quản lý tồn — nơi áp sản phẩm không theo dõi tồn và hạn mức đặt trước |
