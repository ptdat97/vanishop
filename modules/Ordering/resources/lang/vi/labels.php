<?php

/*
| Nhãn hiển thị trong Admin (StatusBadge, bộ lọc, lịch sử đơn) theo mã trạng thái; mã chưa có nhãn hiển thị nguyên mã.
*/

return [
    'order_status' => ['pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'processing' => 'Đang xử lý', 'completed' => 'Hoàn tất', 'cancelled' => 'Đã huỷ'],
    'payment_status' => ['unpaid' => 'Chưa thanh toán', 'authorized' => 'Đã cấp phép', 'paid' => 'Đã thanh toán', 'partially_refunded' => 'Hoàn một phần', 'refunded' => 'Đã hoàn tiền', 'cod_pending' => 'COD chờ thu', 'cod_collected' => 'COD đã thu', 'failed' => 'Thất bại'],
    'event_type' => ['placed' => 'Đặt hàng', 'status_changed' => 'Đổi trạng thái', 'payment_status_changed' => 'Thanh toán', 'address_changed' => 'Đổi địa chỉ', 'note' => 'Ghi chú'],
];
