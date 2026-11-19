<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'return_controller' => [
        'could_not_identify_vnpay_transaction' => 'Không xác định được giao dịch VNPay.',
        'vnpay_payment_successful_thank_you' => 'Thanh toán VNPay thành công. Cảm ơn bạn!',
        'vnpay_payment_was_not_successful' => 'Thanh toán VNPay chưa thành công. Bạn có thể thử lại.',
    ],
    'vn_pay_gateway' => [
        'already_refunded' => 'Đã hoàn trước đó.',
        'code' => ' (mã ',
        'field_field_missing_from_vnpay' => 'Thiếu trường :field trong IPN VNPay.',
        'refund_manually' => ' — hoàn tiền thủ công.',
        'vnpay_atm_cards_international_cards' => 'VNPay (thẻ ATM, thẻ quốc tế, QR)',
        'vnpay_declined_refund' => 'VNPay từ chối hoàn tiền',
        'vnpay_has_not_confirmed_original' => 'VNPay chưa xác nhận giao dịch thanh toán gốc — hoàn tiền thủ công.',
        'vnpay_ipn_another_website_code' => 'IPN VNPay của mã website khác.',
        'vnpay_ipn_signature_does_not' => 'Chữ ký IPN VNPay không khớp.',
    ],
    'vn_pay_service_provider' => [
        'least_300_leave_empty_use' => 'Tối thiểu 300. Để trống = theo VNPAY_TTL (900).',
        'leave_empty_use_vnpay_tmn' => 'Để trống = theo VNPAY_TMN_CODE.',
        'payment_timeout_seconds' => 'Thời gian chờ thanh toán (giây)',
        'secret_key_vnp_hashsecret' => 'Khoá bí mật (vnp_HashSecret)',
        'stored_encrypted_leave_empty_use' => 'Lưu mã hoá. Để trống = theo VNPAY_HASH_SECRET.',
        'test_environment_sandbox' => 'Môi trường thử nghiệm (sandbox)',
        'turn_off_production_leave_empty' => 'Tắt khi chạy thật. Để trống = theo VNPAY_SANDBOX.',
        'website_code_vnp_tmncode' => 'Mã website (vnp_TmnCode)',
    ],
];
