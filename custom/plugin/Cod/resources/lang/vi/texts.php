<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'cod_gateway' => [
        'cod_has_no_callback' => 'COD không có callback.',
        'cod_refunds_manual' => 'COD hoàn tiền thủ công.',
    ],
    'cod_service_provider' => [
        'auto_confirm_cod_orders' => 'Tự xác nhận đơn COD',
        'leave_empty_use_vani_cod' => 'Để trống = theo VANI_COD_MAX_AMOUNT.',
        'maximum_cod_order' => 'Đơn COD tối đa (₫)',
        'no_staff_call_message_confirm' => 'Không — nhân viên gọi/nhắn xác nhận mọi đơn (khuyến nghị)',
        'returning_customer_minimum_delivered_orders' => 'Khách quen: số đơn đã giao tối thiểu',
        'returning_customers_identified_phone_number' => 'Khách quen tính theo số điện thoại. Để trống = theo VANI_COD_AUTO_CONFIRM (mặc định tắt).',
        'returning_customers_only_received_orders' => 'Chỉ khách quen (đã nhận hàng, chưa từng bị hoàn về)',
        'used_returning_customers_only' => 'Dùng khi chọn "Chỉ khách quen".',
        'yes_confirm_every_cod_order' => 'Có — xác nhận ngay mọi đơn COD',
    ],
];
