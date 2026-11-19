<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'cod_gateway' => [
        'cod_has_no_callback' => 'COD has no callback.',
        'cod_refunds_manual' => 'COD refunds are manual.',
    ],
    'cod_service_provider' => [
        'auto_confirm_cod_orders' => 'Auto-confirm COD orders',
        'leave_empty_use_vani_cod' => 'Leave empty = use VANI_COD_MAX_AMOUNT.',
        'maximum_cod_order' => 'Maximum COD order (₫)',
        'no_staff_call_message_confirm' => 'No — staff call/message to confirm every order (recommended)',
        'returning_customer_minimum_delivered_orders' => 'Returning customer: minimum delivered orders',
        'returning_customers_identified_phone_number' => 'Returning customers are identified by phone number. Leave empty = use VANI_COD_AUTO_CONFIRM (off by default).',
        'returning_customers_only_received_orders' => 'Returning customers only (received orders, never returned to sender)',
        'used_returning_customers_only' => 'Used with "Returning customers only".',
        'yes_confirm_every_cod_order' => 'Yes — confirm every COD order immediately',
    ],
];
