<?php

/*
 * Cấu hình mặc định của vani.cod — Admin → Cấu hình (namespace vani.cod) ghi đè.
 */

return [
    // Đơn COD tối đa (₫); null = không giới hạn.
    'max_amount' => env('VANI_COD_MAX_AMOUNT', 20000000) === null ? null : (int) env('VANI_COD_MAX_AMOUNT', 20000000),

    // Tự xác nhận đơn COD sau khi đặt. Mặc định tắt (ADR-046): nhân viên gọi/nhắn xác nhận mọi đơn COD.
    // Bật (= xác nhận mọi đơn) chỉ khi chưa chọn "Tự xác nhận đơn COD" trong Admin → Cấu hình.
    'auto_confirm' => (bool) env('VANI_COD_AUTO_CONFIRM', false),
];
