<?php

/*
| Hook công khai của module Extension. Danh mục tổng: docs/04-extension/extension-point-catalog.md
*/

return [
    'vani.admin.dashboard.cards' => [
        'type' => 'slot',
        'visibility' => 'public',
        'since' => '0.1',
        'args' => [],
        'description' => 'Thêm card vào trang tổng quan Admin. Listener trả về array{title: string, body: string}.',
    ],
];
