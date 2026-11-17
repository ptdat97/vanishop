<?php

/*
| Nhãn hiển thị trong Admin (StatusBadge, Select) theo mã; mã chưa có nhãn hiển thị nguyên mã.
*/

return [
    'shipment_status' => ['pending_booking' => 'Awaiting booking', 'booking_failed' => 'Booking failed', 'created' => 'Booked', 'picked_up' => 'Picked up', 'in_transit' => 'In transit', 'out_for_delivery' => 'Out for delivery', 'failed_attempt' => 'Delivery attempt failed', 'delivered' => 'Delivered', 'returning' => 'Returning', 'returned' => 'Returned', 'cancelled' => 'Cancelled'],
    'cod_statement_status' => ['matched' => 'Matched', 'amount_mismatch' => 'Amount mismatch', 'not_found' => 'No such shipment', 'not_delivered' => 'Shipment not delivered', 'already' => 'Already reconciled', 'duplicate' => 'Repeated in file', 'invalid' => 'Unreadable amount', 'header_missing' => 'Missing tracking / COD amount column'],
];
