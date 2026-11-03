<?php

/*
| Hook công khai của module Ordering. Danh mục tổng: docs/04-extension/extension-point-catalog.md
*/

return [
    'vani.admin.order.sidebar' => [
        'type' => 'slot',
        'visibility' => 'public',
        'since' => '0.1',
        'args' => ['order' => 'Modules\\Ordering\\Contracts\\Data\\OrderDetail'],
        'description' => 'Panel bên phải trang đơn Admin. Listener trả về array{title: string, rows: list<array{label: string, value: string}>, link?: array{label: string, url: string}}.',
    ],
    'vani.service.ordering.order_detail' => [
        'type' => 'filter',
        'visibility' => 'public',
        'since' => '0.3.47',
        'on_error' => 'skip',
        'args' => ['detail' => 'Modules\\Ordering\\Contracts\\Data\\OrderDetail', 'order' => 'Modules\\Ordering\\Persistence\\Models\\Order', 'masked' => 'bool'],
        'description' => 'Chi tiết đơn cho Admin và khách (masked = true: trang tra cứu công khai). Chỉ hiển thị — không ghi DB; số tiền đã chốt trên đơn.',
    ],
    'vani.order.before_create' => [
        'type' => 'filter',
        'visibility' => 'public',
        'since' => '0.3',
        'args' => ['meta' => 'array', 'request' => 'Modules\\Checkout\\Contracts\\Data\\CheckoutRequest', 'totals' => 'Modules\\Checkout\\Contracts\\Data\\Totals'],
        'description' => 'Chạy TRONG transaction đặt hàng (không I/O mạng): bổ sung orders.meta, khoá theo plugin id (vd. meta["vani.einvoice"]). Không sửa được giá/dòng. Dữ liệu khách nhập ở checkout nằm trong CheckoutRequest::$extra[<plugin id>] — kiểm tra bằng vani.checkout.before_validate.',
    ],
];
