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
    'vani.customer.before_delete' => [
        'type' => 'validate',
        'visibility' => 'public',
        'since' => '0.3.57',
        'args' => ['customerId' => 'int'],
        'runs' => 'trước transaction, trước khi kiểm OTP — chỉ đọc, không I/O mạng',
        'description' => 'Việc còn dở chặn khách tự xoá tài khoản. Listener trả list<array{type: string, reference: string, label: string}> (vd. đơn chưa hoàn tất, đổi trả đang mở, điểm thưởng chờ). Nhân viên ẩn danh hoá trong Admin không qua hook này.',
    ],
];
