<?php

/*
| Hook công khai của module Checkout. Danh mục tổng: docs/04-extension/extension-point-catalog.md
*/

return [
    'vani.checkout.before_validate' => [
        'type' => 'validate',
        'visibility' => 'public',
        'since' => '0.1',
        'args' => ['request' => 'Modules\\Checkout\\Contracts\\Data\\CheckoutRequest'],
        'description' => 'Kiểm tra bổ sung trước validator Core (chống bom hàng, quy tắc riêng brand). Trả list<string> lỗi. Chạy trong transaction đặt hàng, không I/O mạng.',
    ],
    'vani.checkout.after_validate' => [
        'type' => 'validate',
        'visibility' => 'public',
        'since' => '0.1',
        'args' => ['request' => 'Modules\\Checkout\\Contracts\\Data\\CheckoutRequest', 'totals' => 'Modules\\Checkout\\Contracts\\Data\\Totals'],
        'description' => 'Kiểm tra dựa trên tổng đã tính (vd. giá trị đơn tối thiểu cho COD). Trả list<string> lỗi.',
    ],
    'vani.checkout.payment_methods' => [
        'type' => 'filter',
        'visibility' => 'public',
        'since' => '0.1',
        'args' => ['methods' => 'list<array{code: string, label: string}>', 'totals' => 'Modules\\Checkout\\Contracts\\Data\\Totals'],
        'description' => 'Ẩn/hiện phương thức thanh toán (vd. tắt COD cho đơn lớn).',
    ],
    'vani.checkout.shipping_options' => [
        'type' => 'filter',
        'visibility' => 'public',
        'since' => '0.1',
        'args' => ['options' => 'list<Modules\\Checkout\\Contracts\\Data\\ShippingOption>', 'context' => 'Modules\\Checkout\\Contracts\\Data\\TotalsContext'],
        'description' => 'Sửa danh sách phương thức giao (lọc, đổi phí). Không gọi mạng.',
    ],
    'vani.order.after_create' => [
        'type' => 'action',
        'visibility' => 'public',
        'since' => '0.1',
        'args' => ['order' => 'Modules\\Ordering\\Contracts\\Data\\PlacedOrder'],
        'description' => 'Chạy TRONG transaction đặt hàng: plugin chỉ ghi DB của mình (attribution, điểm chờ…), không I/O mạng. Lỗi → đơn rollback.',
    ],
];
