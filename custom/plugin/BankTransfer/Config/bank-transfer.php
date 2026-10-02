<?php

/*
 * Cấu hình mặc định của vani.bank-transfer — Admin → Cấu hình (namespace vani.bank-transfer) ghi đè.
 * Thiếu số tài khoản → phương thức ẩn ở checkout.
 */

return [
    'account' => [
        'bank' => env('VANI_BANK_TRANSFER_BANK', ''),
        'account_number' => env('VANI_BANK_TRANSFER_ACCOUNT', ''),
        'account_name' => env('VANI_BANK_TRANSFER_NAME', ''),
    ],

    // Thời gian chờ khách chuyển khoản (giây) trước khi thanh toán hết hạn.
    'ttl' => (int) env('VANI_BANK_TRANSFER_TTL', 86400),
];
