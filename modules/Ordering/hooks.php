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
];
