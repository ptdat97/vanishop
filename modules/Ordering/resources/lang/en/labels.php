<?php

/*
| Nhãn hiển thị trong Admin (StatusBadge, bộ lọc, lịch sử đơn) theo mã trạng thái; mã chưa có nhãn hiển thị nguyên mã.
*/

return [
    'order_status' => ['pending' => 'Pending', 'confirmed' => 'Confirmed', 'processing' => 'Processing', 'completed' => 'Completed', 'cancelled' => 'Cancelled'],
    'payment_status' => ['unpaid' => 'Unpaid', 'authorized' => 'Authorized', 'paid' => 'Paid', 'partially_refunded' => 'Partially refunded', 'refunded' => 'Refunded', 'cod_pending' => 'COD pending', 'cod_collected' => 'COD collected', 'failed' => 'Failed'],
    'fulfillment_status' => ['unfulfilled' => 'Unfulfilled', 'allocated' => 'Awaiting carrier', 'partially_shipped' => 'Partially shipped', 'shipped' => 'Shipping', 'delivered' => 'Delivered', 'returned_to_sender' => 'Returned to sender'],
    'return_status' => ['none' => 'None', 'requested' => 'Requested', 'in_progress' => 'In progress', 'partially_returned' => 'Partially returned', 'returned' => 'Returned'],
    'event_type' => ['status_overridden' => 'Status changed out of sequence', 'placed' => 'Placed', 'status_changed' => 'Status changed', 'payment_status_changed' => 'Payment', 'address_changed' => 'Address changed', 'note' => 'Note', 'delivery_issue_changed' => 'Delivery issue'],
    'delivery_issue' => ['booking_failed' => 'Booking failed', 'failed_attempt' => 'Delivery attempt failed', 'returning' => 'Returning to sender', 'returned' => 'Returned to sender'],
    'stage' => ['to_confirm' => 'To confirm', 'awaiting_payment' => 'Awaiting payment', 'to_ship' => 'To hand to carrier', 'shipping' => 'Shipping', 'issue' => 'Delivery issue', 'returns' => 'Returns', 'delivered' => 'Delivered', 'completed' => 'Completed', 'cancelled' => 'Cancelled'],
    'source' => ['web' => 'Website', 'app' => 'App', 'zalo' => 'Zalo', 'admin' => 'Admin', 'pos' => 'Store', 'exchange' => 'Exchange'],
    // Trạng thái hiển thị cho khách (tính từ 4 chiều của đơn — CustomerStatus).
    'customer_status' => ['cancelled' => 'Cancelled', 'returning' => 'Return in progress', 'returned' => 'Returned', 'completed' => 'Completed', 'delivered' => 'Delivered', 'shipping' => 'Shipping', 'delivery_failed' => 'Delivery failed', 'awaiting_payment' => 'Awaiting payment', 'awaiting_confirmation' => 'Awaiting confirmation', 'preparing' => 'Preparing'],
];
