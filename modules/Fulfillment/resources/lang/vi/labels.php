<?php

/*
| Nhãn hiển thị trong Admin (StatusBadge, Select) theo mã; mã chưa có nhãn hiển thị nguyên mã.
*/

return [
    'shipment_status' => ['pending_booking' => 'Chờ đặt vận đơn', 'booking_failed' => 'Đặt lỗi', 'created' => 'Đã có mã', 'picked_up' => 'Đã bàn giao hãng', 'in_transit' => 'Đang vận chuyển', 'out_for_delivery' => 'Đang giao', 'failed_attempt' => 'Giao không thành công', 'delivered' => 'Đã giao', 'returning' => 'Đang hoàn', 'returned' => 'Đã hoàn về', 'cancelled' => 'Đã huỷ'],
    'cod_statement_status' => ['matched' => 'Khớp', 'amount_mismatch' => 'Lệch tiền', 'not_found' => 'Không có vận đơn', 'not_delivered' => 'Vận đơn chưa giao', 'already' => 'Đã đối soát trước đó', 'duplicate' => 'Lặp trong file', 'invalid' => 'Số tiền không đọc được', 'header_missing' => 'Thiếu cột mã vận đơn / tiền thu hộ'],
];
