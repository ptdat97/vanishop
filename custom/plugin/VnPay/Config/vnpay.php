<?php

/*
 * Cấu hình mặc định của plugin vani.vnpay. Admin → Cấu hình (namespace vani.vnpay) ghi đè các giá trị này.
 */

return [
    // Mã website (vnp_TmnCode) và khoá bí mật (vnp_HashSecret) VNPay cấp.
    'tmn_code' => env('VNPAY_TMN_CODE', ''),
    'hash_secret' => env('VNPAY_HASH_SECRET', ''),

    // true: môi trường thử nghiệm sandbox.vnpayment.vn.
    'sandbox' => (bool) env('VNPAY_SANDBOX', true),

    // Thời gian khách được thanh toán (giây) — vnp_ExpireDate.
    'ttl' => (int) env('VNPAY_TTL', 900),

    // URL khách quay về sau khi thanh toán. Để trống: trang của plugin (/p/vani-vnpay/return) → trang đơn hàng.
    // Storefront headless đặt URL của frontend (frontend gọi GET /api/storefront/v1/payments/{id} để biết kết quả).
    'return_url' => env('VNPAY_RETURN_URL', ''),
];
