<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'config_ghn' => [
        'service_express' => 'Giao Hàng Nhanh (Nhanh 1-2 ngày)',
        'service_standard' => 'Giao Hàng Nhanh (Tiêu chuẩn 2-3 ngày)',
    ],
    'ghn_carrier' => [
        'ghn_status_ghn_status' => 'GHN trạng thái: :ghn_status',
        'ghn_webhook_token_does_not' => 'Token webhook GHN không khớp bí mật.',
        'carrier_name' => 'Giao Hàng Nhanh (GHN)',
        'service_standard' => 'Giao Hàng Nhanh (Tiêu chuẩn 2-3 ngày)',
        'required_ghn_webhook_data_missing' => 'Thiếu dữ liệu webhook GHN bắt buộc.',
    ],
];
