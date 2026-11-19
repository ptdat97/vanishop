<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'bank_transfer_service_provider' => [
        'account_holder' => 'Chủ tài khoản',
        'account_number' => 'Số tài khoản',
        'bank' => 'Ngân hàng',
        'missing_account_number_method_hidden' => 'Thiếu số tài khoản → phương thức ẩn ở checkout.',
    ],
    'manual_bank_transfer_gateway' => [
        'manual_bank_transfer_has_no' => 'Chuyển khoản thủ công không có callback.',
        'manual_bank_transfer_refund' => 'Hoàn tiền chuyển khoản thủ công.',
    ],
];
