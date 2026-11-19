<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'bank_statement_service_provider' => [
        'confirmed_confirmed_transfers_mismatched_mismatched' => 'Đã xác nhận :confirmed chuyển khoản; :mismatched giao dịch lệch (xem "Đối soát lệch"); bỏ qua :duplicate giao dịch đã nhập.',
        'import_bank_statement' => 'Nhập sao kê ngân hàng',
        'xlsx_csv_file_exported_bank' => 'File .xlsx/.csv ngân hàng xuất: cột "Mã GD", "Số tiền", "Nội dung". Chuyển khoản có số đơn trong nội dung và đúng số tiền được xác nhận ngay.',
    ],
    'statement_matcher' => [
        'bank_statement_reference' => 'Sao kê ngân hàng: :reference',
        'no_order_number_found_description' => 'Không tìm thấy số đơn trong nội dung: ',
        'order_number' => 'Đơn :number',
        'order_number_amount_does_not' => 'Đơn :number: số tiền không khớp',
        'order_number_not_awaiting_bank' => 'Đơn :number: không chờ chuyển khoản (:status)',
        'statement_needs_columns_m_gd' => 'Sao kê cần cột "Mã GD", "Số tiền", "Nội dung" ở dòng đầu.',
    ],
];
