# vani.reports

Plugin báo cáo bán hàng (Ring 3, `kind: analytics`), là implementation tham chiếu của `DashboardWidget` và `ReportProvider` (ADR-030 W6b). Plugin không có bảng riêng: mọi số liệu đọc qua service contract `Modules\Ordering\Contracts\OrderStatistics`, `Modules\Returns\Contracts\ReturnStatistics`, `Modules\Fulfillment\Contracts\DeliveryStatistics`.

- Quyền `reports.view`: thấy các widget doanh thu và Admin → Báo cáo. Widget "Đơn cần xử lý" chỉ cần quyền `orders.view`.
- Widget Tổng quan: doanh thu hôm nay, đơn cần xử lý, doanh thu 30 ngày (kèm giá trị TB/đơn), biểu đồ 14 ngày, sản phẩm doanh thu cao.
- Báo cáo: doanh thu theo ngày, theo sản phẩm, theo thương hiệu, theo phương thức thanh toán, theo kênh. Mỗi báo cáo chọn được khoảng thời gian và xuất CSV (Core lo phần CSV).
- Lợi nhuận gộp theo ngày và theo sản phẩm (1.3.5) dùng giá vốn theo SKU được chụp khi đặt đơn qua hook `vani.pricing.unit_costs`. Các dòng cũ và dòng thiếu giá vốn lúc đặt được ghi riêng trong doanh thu thiếu giá vốn. Báo cáo vẫn đọc giá vốn đã chụp khi nguồn giá vốn hiện tại tắt. Tỷ lệ COD giao thất bại dựa trên số kiện có kết quả trong kỳ, với kiện hoàn về được tính vào ngày bắt đầu hoàn.
- Lợi nhuận sau trả hàng và phí giao theo ngày đặt (1.5.0): hàng trả, tiền hoàn, phí hãng và ship trả hàng cửa hàng chịu của đơn tính về ngày đặt của đơn gốc; hàng trả đã nhập lại kho trả lại giá vốn. Kèm tỷ lệ trả theo kỳ bán và số kiện chưa có phí hãng.
- Gợi ý nhập lại và hàng bán chậm (1.6.0): tốc độ bán = số bán trong kỳ / số ngày của kỳ. Gợi ý nhập = số bán trong kỳ − tồn bán được − hàng đang về (đủ bán thêm một kỳ). Hàng bán chậm = SKU còn tồn cần hơn 90 ngày mới bán hết hoặc không bán được trong kỳ. Bỏ qua sản phẩm không theo dõi tồn.
- Đổi trả theo sản phẩm (số trả, tỷ lệ so với số bán cùng kỳ, tiền hoàn) và lý do trả & giao thất bại COD — qua `ReturnStatistics`, `DeliveryStatistics`.
- Huỷ đơn theo lý do (1.3.6) nhóm theo mã và ngày huỷ. Các lượt chưa chọn mã nằm trong nhóm chưa phân loại. Báo cáo COD chờ đối soát lọc theo ngày giao và nhóm số ngày đã chờ theo hãng. Trạng thái đối soát được lấy tại lúc mở báo cáo.
- Doanh thu tính bằng `total_amount` của các đơn **không bị huỷ**, theo `placed_at`, nhóm ngày theo giờ Việt Nam.
- Tắt plugin thì widget và menu Báo cáo biến mất; Core vẫn giữ slot card cũ.
