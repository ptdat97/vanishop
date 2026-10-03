<?php

return [
    'policy' => [
        'not_delivered' => 'Đơn hàng chưa được giao, chưa thể đổi/trả.',
        'window_expired' => 'Đã quá thời hạn đổi/trả.',
    ],
    'quantity_exceeded' => 'Số lượng đổi/trả vượt số có thể trả (còn :allowed).',
    'transition_invalid' => 'Không thể chuyển yêu cầu đổi/trả từ :from sang :to.',
    'refund_exceeds' => 'Số tiền hoàn vượt số tiền tính được cho yêu cầu này.',
    'unknown_lines' => 'Tình trạng hàng gửi kèm dòng không thuộc yêu cầu đổi/trả này.',
    'stale' => 'Yêu cầu đã được người khác cập nhật. Vui lòng tải lại trang.',
    'updated' => 'Đã cập nhật yêu cầu đổi/trả.',
    'received' => 'Đã nhận hàng trả.',
    'resolved' => 'Đã hoàn tất đổi/trả.',
];
