<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'account_lifecycle' => [
        'customer_cannot_merged_itself' => 'Không thể hợp nhất khách với chính nó.',
        'only_two_active_customers_merged' => 'Chỉ hợp nhất được hai khách đang hoạt động.',
    ],
    'customer_controller' => [
        'target_customer_not_found' => 'Không tìm thấy khách đích.',
    ],
    'customer_service_provider' => [
        'anonymise_customers_deletion_requests' => 'Ẩn danh hoá khách hàng theo yêu cầu xoá',
        'customers' => 'Khách hàng',
        'manage_customer_groups_assign_groups' => 'Quản lý nhóm khách và gán nhóm/tag cho khách',
        'merge_duplicate_customers' => 'Hợp nhất khách hàng trùng',
        'otp_delivery_channel' => 'Kênh gửi OTP',
        'view_customers_owner_level' => 'Xem khách hàng (cấp Owner)',
    ],
    'order_customer_notes_panel' => [
        'add_customer_service_note' => 'Thêm ghi chú CSKH',
        'customer_profile' => 'Hồ sơ khách',
        'customer_service_notes' => 'Ghi chú CSKH',
        'no_notes_yet' => 'Chưa có ghi chú',
        'note_customer_pick_up_after' => 'Ghi chú (vd. khách hẹn nhận sau 18h)',
    ],
];
