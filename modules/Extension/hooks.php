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
    'vani.admin.page.*' => [
        'type' => 'filter',
        'visibility' => 'public',
        'stability' => 'experimental',
        'on_error' => 'skip',
        'since' => '0.3.8',
        'args' => ['props' => 'array<string, mixed>', 'component' => 'string'],
        'description' => 'Điểm tự động cho MỌI trang Admin (Inertia): sửa/thêm props trước khi render. Tên = component viết thường, "::" và "/" thành "." (Ordering::Orders/Show → vani.admin.page.ordering.orders.show). Thêm khoá dưới tên plugin; không xoá khoá của Core.',
    ],
];
