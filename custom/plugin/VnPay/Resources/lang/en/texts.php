<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'vn_pay_gateway' => [
        'code' => ' (code',
        'field_field_missing_from_vnpay' => 'Field :field is missing from the VNPay IPN.',
        'refund_manually' => ' — refund manually.',
        'vnpay_atm_cards_international_cards' => 'VNPay (ATM cards, international cards, QR)',
        'vnpay_declined_refund' => 'VNPay declined the refund',
        'vnpay_has_not_confirmed_original' => 'VNPay has not confirmed the original payment — refund manually.',
        'vnpay_ipn_another_website_code' => 'VNPay IPN for another website code.',
        'vnpay_ipn_signature_does_not' => 'VNPay IPN signature does not match.',
    ],
    'vn_pay_service_provider' => [
        'least_300_leave_empty_use' => 'At least 300. Leave empty = use VNPAY_TTL (900).',
        'leave_empty_use_vnpay_tmn' => 'Leave empty = use VNPAY_TMN_CODE.',
        'payment_timeout_seconds' => 'Payment timeout (seconds)',
        'secret_key_vnp_hashsecret' => 'Secret key (vnp_HashSecret)',
        'stored_encrypted_leave_empty_use' => 'Stored encrypted. Leave empty = use VNPAY_HASH_SECRET.',
        'test_environment_sandbox' => 'Test environment (sandbox)',
        'turn_off_production_leave_empty' => 'Turn off in production. Leave empty = use VNPAY_SANDBOX.',
        'website_code_vnp_tmncode' => 'Website code (vnp_TmnCode)',
    ],
];
