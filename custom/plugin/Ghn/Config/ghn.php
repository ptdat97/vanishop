<?php

/*
 * Cấu hình plugin vani.ghn — nạp qua PluginServiceProvider::register().
 */

return [
    // Client ID và API token của shop trên GHN OpenAPI.
    'client_id' => env('GHN_CLIENT_ID', ''),
    'token' => env('GHN_TOKEN', ''),
    'shop_id' => env('GHN_SHOP_ID', ''),

    // Khoá dùng đối chiếu Token trong webhook trạng thái vận đơn.
    'webhook_secret' => env('GHN_WEBHOOK_SECRET', ''),

    // base64 của client_id:token, GHN yêu cầu cho header Authorization.
    'api_base' => env('GHN_API_BASE', 'https://api.ghn.vn'),

    // Dịch vụ GHN và cước niêm yết tương ứng (₫) dùng khi không gọi được API báo giá.
    'services' => [
        'ghn_standard' => [
            'label' => 'Giao Hàng Nhanh (Tiêu chuẩn 2-3 ngày)',
            'fee' => (int) env('GHN_FEE_STANDARD', 30000),
        ],
        'ghn_express' => [
            'label' => 'Giao Hàng Nhanh (Nhanh 1-2 ngày)',
            'fee' => (int) env('GHN_FEE_EXPRESS', 55000),
        ],
    ],
];
