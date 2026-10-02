<?php

/*
 * Cấu hình plugin vani.vietqr — nạp qua PluginServiceProvider::register().
 * Tài khoản nhận tiền của cửa hàng.
 */

return [
    'account' => [
        'bank_id' => env('VIETQR_BANK_ID', '970436'),
        'account_no' => env('VIETQR_ACCOUNT_NO', ''),
        'account_name' => env('VIETQR_ACCOUNT_NAME', ''),
        'template' => env('VIETQR_TEMPLATE', 'compact2'),
    ],

    // Khoá HMAC dùng xác minh webhook/IPN. Bắt buộc đặt ở môi trường thật.
    'secret' => env('VIETQR_SECRET', ''),

    // Thời gian khách được quét QR (giây).
    'ttl' => (int) env('VIETQR_TTL', 900),
];
