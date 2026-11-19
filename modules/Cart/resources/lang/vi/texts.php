<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'settings' => [
        'abandoned_after_minutes' => 'Giỏ bị bỏ quên sau (phút)',
        'abandoned_after_minutes_help' => 'Giỏ có liên hệ không hoạt động quá số phút này được coi là bỏ quên — plugin nhắc giỏ hàng gửi tin.',
        'max_line_quantity' => 'Số lượng tối đa mỗi sản phẩm trong giỏ',
        'max_line_quantity_help' => 'Khách không thêm quá số này cho một mẫu/size trong một đơn.',
        'max_lines' => 'Số sản phẩm khác nhau tối đa trong giỏ',
        'max_lines_help' => 'Giới hạn số dòng của một giỏ (chống giỏ ảo, đơn quá lớn).',
        'ttl_days' => 'Giữ giỏ không hoạt động (ngày)',
        'ttl_days_help' => 'Giỏ không hoạt động quá số ngày này bị xoá khỏi hệ thống.',
    ],
];
