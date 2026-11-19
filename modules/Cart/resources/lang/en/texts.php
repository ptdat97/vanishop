<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'settings' => [
        'abandoned_after_minutes' => 'Cart abandoned after (minutes)',
        'abandoned_after_minutes_help' => 'Carts with contact details idle longer than this count as abandoned — cart reminder plugins send messages.',
        'max_line_quantity' => 'Maximum quantity per product in cart',
        'max_line_quantity_help' => 'Customers cannot add more than this for one variant in an order.',
        'max_lines' => 'Maximum distinct products in cart',
        'max_lines_help' => 'Limit on cart lines (prevents fake carts and oversized orders).',
        'ttl_days' => 'Keep idle carts (days)',
        'ttl_days_help' => 'Carts idle longer than this are deleted.',
    ],
];
