# vani.reports

Plugin báo cáo bán hàng (Ring 3, `kind: analytics`), là implementation tham chiếu của `DashboardWidget` và `ReportProvider` (ADR-030 W6b). Plugin không có bảng riêng: mọi số liệu đọc qua service contract `Modules\Ordering\Contracts\OrderStatistics`, `Modules\Returns\Contracts\ReturnStatistics`, `Modules\Fulfillment\Contracts\DeliveryStatistics`.

- Quyền `reports.view`: thấy các widget doanh thu và Admin → Báo cáo. Widget "Đơn cần xử lý" chỉ cần quyền `orders.view`.
- Widget Tổng quan: doanh thu hôm nay, đơn cần xử lý, doanh thu 30 ngày (kèm giá trị TB/đơn), biểu đồ 14 ngày, sản phẩm doanh thu cao.
- Báo cáo: doanh thu theo ngày, theo sản phẩm, theo thương hiệu, theo phương thức thanh toán, theo kênh. Mỗi báo cáo chọn được khoảng thời gian và xuất CSV (Core lo phần CSV).
- Lợi nhuận gộp theo ngày / theo sản phẩm (1.3.0): doanh thu dòng đơn − số lượng × giá vốn đơn vị lấy qua hook `vani.pricing.unit_costs` (plugin `vani.cost-price` cấp). SKU thiếu giá vốn được ghi "Thiếu giá vốn", doanh thu của nó tách riêng, không coi giá vốn = 0. Giá vốn là giá hiện tại của SKU (backlog L43).
- Đổi trả theo sản phẩm (số trả, tỷ lệ so với số bán cùng kỳ, tiền hoàn) và lý do trả & giao thất bại COD — qua `ReturnStatistics`, `DeliveryStatistics`.
- Doanh thu tính bằng `total_amount` của các đơn **không bị huỷ**, theo `placed_at`, nhóm ngày theo giờ Việt Nam.
- Tắt plugin thì widget và menu Báo cáo biến mất; Core vẫn giữ slot card cũ.
