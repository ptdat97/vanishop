<?php

/*
| Nhãn hiển thị trong Admin (StatusBadge, bộ lọc, lịch sử đơn) theo mã trạng thái; mã chưa có nhãn hiển thị nguyên mã.
*/

return [
    'order_status' => ['pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'processing' => 'Đang xử lý', 'completed' => 'Hoàn tất', 'cancelled' => 'Đã huỷ'],
    'payment_status' => ['unpaid' => 'Chưa thanh toán', 'authorized' => 'Đã cấp phép', 'paid' => 'Đã thanh toán', 'partially_refunded' => 'Hoàn một phần', 'refunded' => 'Đã hoàn tiền', 'cod_pending' => 'COD chờ thu', 'cod_collected' => 'COD đã thu', 'failed' => 'Thất bại'],
    'fulfillment_status' => ['unfulfilled' => 'Chưa giao', 'allocated' => 'Chờ giao hãng', 'partially_shipped' => 'Giao một phần', 'shipped' => 'Đang giao', 'delivered' => 'Đã giao', 'returned_to_sender' => 'Hoàn về'],
    'return_status' => ['none' => 'Không', 'requested' => 'Chờ duyệt', 'in_progress' => 'Đang xử lý', 'partially_returned' => 'Trả một phần', 'returned' => 'Đã trả hàng'],
    'event_type' => ['status_overridden' => 'Đổi trạng thái ngoài trình tự', 'placed' => 'Đặt hàng', 'status_changed' => 'Đổi trạng thái', 'payment_status_changed' => 'Thanh toán', 'address_changed' => 'Đổi địa chỉ', 'note' => 'Ghi chú', 'delivery_issue_changed' => 'Sự cố giao hàng'],
    // Giai đoạn vận hành (hàng đợi việc ở danh sách đơn — OrderStage).
    'delivery_issue' => ['booking_failed' => 'Đặt vận đơn lỗi', 'failed_attempt' => 'Giao không thành công', 'returning' => 'Đang hoàn về', 'returned' => 'Đã hoàn về'],
    'stage' => ['to_confirm' => 'Cần xác nhận', 'awaiting_payment' => 'Chờ thanh toán', 'to_ship' => 'Chờ giao hãng', 'shipping' => 'Đang giao', 'issue' => 'Sự cố giao hàng', 'returns' => 'Đổi/trả', 'delivered' => 'Đã giao', 'completed' => 'Hoàn tất', 'cancelled' => 'Đã huỷ'],
    'source' => ['web' => 'Website', 'app' => 'App', 'zalo' => 'Zalo', 'admin' => 'Admin', 'pos' => 'Cửa hàng', 'exchange' => 'Đổi hàng'],
    // Trạng thái hiển thị cho khách (tính từ 4 chiều của đơn — CustomerStatus).
    'customer_status' => ['cancelled' => 'Đã huỷ', 'returning' => 'Đang đổi/trả', 'returned' => 'Đã trả hàng', 'completed' => 'Hoàn tất', 'delivered' => 'Đã giao', 'shipping' => 'Đang giao', 'delivery_failed' => 'Giao không thành công', 'awaiting_payment' => 'Chờ thanh toán', 'awaiting_confirmation' => 'Chờ xác nhận', 'preparing' => 'Đang chuẩn bị hàng'],
];
