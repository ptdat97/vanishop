<?php

/*
 * Cấu hình plugin vani.sms-brandname (nhà cung cấp eSMS) — nạp qua PluginServiceProvider::register().
 * Secret đặt trong .env, không commit (R21).
 */

return [
    'api_base' => env('ESMS_API_BASE', 'https://rest.esms.vn/MainService.svc/json'),
    'api_key' => env('ESMS_API_KEY', ''),
    'secret_key' => env('ESMS_SECRET_KEY', ''),

    // Brandname mặc định và brandname riêng theo mã brand (mỗi brand đăng ký brandname riêng với nhà mạng).
    'brandname' => env('ESMS_BRANDNAME', ''),
    'brandnames' => [
        // 'LU' => 'LUMIERE',
    ],

    // 1 = sandbox của eSMS (không gửi thật, không trừ tiền).
    'sandbox' => (bool) env('ESMS_SANDBOX', false),

    // Nội dung OTP — phải khớp mẫu đã đăng ký với nhà mạng. {code} = mã, không dấu.
    'otp_message' => env('ESMS_OTP_MESSAGE', '{code} la ma xac thuc cua ban. Ma co hieu luc trong 5 phut. Khong chia se ma nay cho bat ky ai.'),

    // Làm OtpSender cho đăng nhập khách (priority thấp hơn ZNS vì đắt hơn).
    'otp_enabled' => (bool) env('ESMS_OTP_ENABLED', true),
];
