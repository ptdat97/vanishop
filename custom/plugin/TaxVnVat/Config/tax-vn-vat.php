<?php

/*
 * Cấu hình mặc định của vani.tax-vn-vat — Admin → Cấu hình (namespace vani.tax-vn-vat) ghi đè.
 */

return [
    // Thuế suất GTGT gồm trong giá, basis points (1000 = 10%). Kế toán/pháp chế xác nhận mức áp dụng hiện hành.
    'rate_bp' => (int) env('VANI_VAT_RATE_BP', 1000),
];
