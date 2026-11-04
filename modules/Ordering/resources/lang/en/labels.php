<?php

/*
| Nhãn hiển thị trong Admin (StatusBadge, bộ lọc, lịch sử đơn) theo mã trạng thái; mã chưa có nhãn hiển thị nguyên mã.
*/

return [
    'order_status' => ['pending' => 'Pending', 'confirmed' => 'Confirmed', 'processing' => 'Processing', 'completed' => 'Completed', 'cancelled' => 'Cancelled'],
    'payment_status' => ['unpaid' => 'Unpaid', 'authorized' => 'Authorized', 'paid' => 'Paid', 'partially_refunded' => 'Partially refunded', 'refunded' => 'Refunded', 'cod_pending' => 'COD pending', 'cod_collected' => 'COD collected', 'failed' => 'Failed'],
    'event_type' => ['placed' => 'Placed', 'status_changed' => 'Status changed', 'payment_status_changed' => 'Payment', 'address_changed' => 'Address changed', 'note' => 'Note'],
];
