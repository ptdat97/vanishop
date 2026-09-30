<?php

/*
| Hook công khai của module Integration. Danh mục tổng: docs/04-extension/extension-point-catalog.md
*/

return [
    'vani.integration.order_payload' => [
        'type' => 'filter',
        'visibility' => 'public',
        'since' => '0.3',
        'args' => ['payload' => 'array', 'order' => 'Modules\\Ordering\\Contracts\\Data\\OrderData'],
        'description' => 'Bổ sung field vào payload canonical vanishop.order.v1 gửi đối tác (không chạy trong transaction). Chỉ được THÊM khoá mới — khoá canonical bị ghi đè/xoá sẽ bị bỏ qua.',
    ],
];
