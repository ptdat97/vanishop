<?php

/*
 * Cấu hình plugin vani.zalo-zns — nạp qua PluginServiceProvider::register(). Secret đặt trong .env (R21).
 */

return [
    'api_base' => env('ZALO_ZNS_API_BASE', 'https://business.openapi.zalo.me'),
    'oauth_base' => env('ZALO_OAUTH_BASE', 'https://oauth.zaloapp.com'),

    // Ứng dụng Zalo liên kết với Official Account.
    'app_id' => env('ZALO_APP_ID', ''),
    'app_secret' => env('ZALO_APP_SECRET', ''),

    // Refresh token ban đầu (lấy khi cấp quyền OA). Zalo cấp refresh token MỚI mỗi lần làm mới; plugin lưu
    // bản mới nhất trong cache store (nên dùng store bền: database/redis).
    'refresh_token' => env('ZALO_REFRESH_TOKEN', ''),
    'cache_store' => env('ZALO_TOKEN_CACHE_STORE'),

    // development = chỉ gửi tới SĐT quản trị/tester của OA, không tính phí.
    'mode' => env('ZALO_ZNS_MODE', 'production'),

    // Mẫu ZNS OTP đã được Zalo duyệt; tham số chứa mã.
    'otp_template_id' => env('ZALO_ZNS_OTP_TEMPLATE_ID', ''),
    'otp_param' => env('ZALO_ZNS_OTP_PARAM', 'otp'),
];
