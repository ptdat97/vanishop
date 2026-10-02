<?php

/*
 * Cấu hình mặc định của vani.shipping-flat-rate — Admin → Cấu hình (namespace vani.shipping-flat-rate) ghi đè.
 */

return [
    // Phí giao cố định (₫).
    'fee' => (int) env('VANI_SHIPPING_FLAT_FEE', 30000),

    // Miễn phí giao khi tiền hàng (sau giảm giá) >= ngưỡng; null = không miễn phí.
    'free_over' => env('VANI_SHIPPING_FREE_OVER', 500000) === null ? null : (int) env('VANI_SHIPPING_FREE_OVER', 500000),
];
