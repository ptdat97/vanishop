<?php

return [
    'policy' => [
        'not_delivered' => 'Đơn hàng chưa được giao, chưa thể đổi/trả.',
        'window_expired' => 'Đã quá thời hạn đổi/trả.',
        'exchange_order' => 'Đơn đổi hàng chỉ đổi tiếp được size/màu cùng mẫu. Muốn trả hoàn tiền hoặc đổi mẫu khác, vui lòng liên hệ CSKH.',
    ],
    'quantity_exceeded' => 'Số lượng đổi/trả vượt số có thể trả (còn :allowed).',
    'transition_invalid' => 'Không thể chuyển yêu cầu đổi/trả từ :from sang :to.',
    'refund_exceeds' => 'Số tiền hoàn vượt số tiền tính được cho yêu cầu này.',
    'unknown_lines' => 'Tình trạng hàng gửi kèm dòng không thuộc yêu cầu đổi/trả này.',
    'stale' => 'Yêu cầu đã được người khác cập nhật. Vui lòng tải lại trang.',
    'created' => 'Đã tạo yêu cầu đổi/trả.',
    'updated' => 'Đã cập nhật yêu cầu đổi/trả.',
    'received' => 'Đã nhận hàng trả.',
    'resolved' => 'Đã hoàn tất đổi/trả.',
    'exchange_invalid' => [
        'lines' => 'Đổi hàng cần chọn sản phẩm thay thế cho mọi dòng trả.',
        'variant' => 'Sản phẩm thay thế không tồn tại hoặc đã ngừng bán.',
    ],
    'exchange_unavailable' => 'Không tạo được đơn đổi hàng: :reason',
    'exchanged' => 'Đã hoàn tất đổi hàng, tạo đơn thay thế :number.',
];
