<?php

/*
| Hook công khai của module Returns. Danh mục tổng: docs/04-extension/extension-point-catalog.md
*/

return [
    'vani.service.returns.view' => [
        'type' => 'filter',
        'visibility' => 'public',
        'since' => '0.3.48',
        'on_error' => 'skip',
        'args' => ['view' => 'Modules\\Returns\\Contracts\\Data\\ReturnView', 'return' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest'],
        'description' => 'Yêu cầu đổi/trả trình bày cho Admin và khách. Chỉ hiển thị: số tiền hoàn và trạng thái do Returns quản lý.',
    ],
];
