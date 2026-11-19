<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'cod_risk_guard_service_provider' => [
        'block_confirming_orders_from_blacklisted' => 'Chặn xác nhận đơn của SĐT trong danh sách đen',
        'one_number_per_line_0912345678' => 'Mỗi dòng một số, vd. 0912345678. Đơn COD của các số này bị đánh dấu rủi ro cao.',
        'order_number_was_returned_sender' => 'Đơn :number bị hoàn về (khách không nhận).',
        'phone_blacklist' => 'Danh sách đen SĐT',
        'phone_number_store_s_blacklist' => 'SĐT trong danh sách đen của cửa hàng — gọi xác nhận với khách rồi gỡ số khỏi danh sách đen (Cấu hình → Chống bom hàng COD)',
        'returned_orders_count_high_risk' => 'Số đơn hoàn về để coi là rủi ro cao',
        'staff_must_remove_number_from' => 'Nhân viên phải gỡ số khỏi danh sách đen (sau khi gọi xác nhận) mới xác nhận được.',
        'tag_bom_hang_add_customer' => 'Gắn nhãn "bom-hang" và ghi chú CSKH khi đơn của khách bị hoàn về',
    ],
    'risk_rules' => [
        'blacklisted_phone_number' => 'SĐT trong danh sách đen',
        'phone_number_already_has_returned' => 'SĐT đã có :returned đơn hoàn về (ngưỡng :threshold).',
        'phone_number_store_s_blacklist' => 'Số điện thoại có trong danh sách đen của cửa hàng.',
        'returned_sender_returned_times' => 'Hoàn về :returned lần',
    ],
];
