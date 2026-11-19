<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'config_ghn' => [
        'service_express' => 'Giao Hàng Nhanh (Express 1-2 days)',
        'service_standard' => 'Giao Hàng Nhanh (Standard 2-3 days)',
    ],
    'ghn_carrier' => [
        'ghn_status_ghn_status' => 'GHN status: :ghn_status',
        'ghn_webhook_token_does_not' => 'GHN webhook token does not match the secret.',
        'carrier_name' => 'Giao Hàng Nhanh (GHN)',
        'service_standard' => 'Giao Hàng Nhanh (Standard 2-3 days)',
        'required_ghn_webhook_data_missing' => 'Required GHN webhook data is missing.',
    ],
];
