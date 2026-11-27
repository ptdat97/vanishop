<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'alert' => [
        'normal' => 'THƯỜNG',
        'ongoing_level' => '[CÒN] [:level]',
        'resolved_level' => '[ĐÃ ỔN] [:level]',
        'urgent' => 'KHẨN',
    ],
    'app_service_provider' => [
        'phone_number_format' => 'Định dạng số điện thoại',
        'view_operations_dashboard_queues_performance' => 'Xem dashboard vận hành (queue, hiệu năng)',
    ],
    'external_calls' => [
        'external_service_call_call_inside' => 'Gọi dịch vụ ngoài [:call] trong transaction DB chưa commit — chuyển lời gọi ra ngoài transaction hoặc dùng DB::afterCommit().',
    ],
    'idempotency_conflict' => [
        'idempotency_key_was_already_used' => 'Idempotency-Key đã được dùng cho một yêu cầu khác.',
        'request_idempotency_key_being_processed' => 'Yêu cầu với Idempotency-Key này đang được xử lý. Vui lòng thử lại sau.',
    ],
    'weekly_schedule_invalid' => 'Lịch lặp theo tuần không hợp lệ: chọn ít nhất một ngày, giờ dạng HH:MM, tối đa 3 khung.',
];
