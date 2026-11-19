<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'bank_statement_service_provider' => [
        'confirmed_confirmed_transfers_mismatched_mismatched' => 'Confirmed :confirmed transfers; :mismatched mismatched transactions (see "Reconciliation mismatches"); skipped :duplicate already-imported transactions.',
        'import_bank_statement' => 'Import bank statement',
        'xlsx_csv_file_exported_bank' => '.xlsx/.csv file exported by the bank: columns "Mã GD", "Số tiền", "Nội dung". Transfers with an order number in the description and the exact amount are confirmed immediately.',
    ],
    'statement_matcher' => [
        'bank_statement_reference' => 'Bank statement: :reference',
        'no_order_number_found_description' => 'No order number found in the description:',
        'order_number' => 'Order :number',
        'order_number_amount_does_not' => 'Order :number: amount does not match',
        'order_number_not_awaiting_bank' => 'Order :number: not awaiting a bank transfer (:status)',
        'statement_needs_columns_m_gd' => 'The statement needs the columns "Mã GD", "Số tiền", "Nội dung" in the first row.',
    ],
];
