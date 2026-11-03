<?php

/*
| Hook công khai của module Customer. Danh mục tổng: docs/04-extension/extension-point-catalog.md
*/

return [
    'vani.service.customer.profile_attributes' => [
        'type' => 'filter',
        'visibility' => 'public',
        'since' => '0.3.47',
        'args' => ['attributes' => 'array<string, mixed>', 'customerId' => 'int'],
        'description' => 'Dữ liệu khách tự sửa hồ sơ, trước khi lưu. Core chỉ lưu full_name, email, birth_date, gender; trường riêng của plugin lưu vào bảng plg_* của plugin.',
    ],
];
