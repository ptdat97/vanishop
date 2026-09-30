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
    'vani.order.before_create' => [
        'type' => 'filter',
        'visibility' => 'public',
        'since' => '0.3',
        'args' => ['meta' => 'array', 'request' => 'Modules\\Checkout\\Contracts\\Data\\CheckoutRequest', 'totals' => 'Modules\\Checkout\\Contracts\\Data\\Totals'],
        'description' => 'Chạy TRONG transaction đặt hàng (không I/O mạng): bổ sung orders.meta, khoá theo plugin id (vd. meta["vani.einvoice"]). Không sửa được giá/dòng. Dữ liệu khách nhập ở checkout nằm trong CheckoutRequest::$extra[<plugin id>] — kiểm tra bằng vani.checkout.before_validate.',
    ],
];
