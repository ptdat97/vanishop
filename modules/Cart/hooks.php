<?php

/*
| Hook công khai của module Cart. Danh mục tổng: docs/04-extension/extension-point-catalog.md
*/

return [
    'vani.cart.validate_line' => [
        'type' => 'validate',
        'visibility' => 'public',
        'since' => '0.1',
        'args' => ['draft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft'],
        'description' => 'Quy tắc riêng khi thêm/sửa dòng giỏ (vd. giới hạn mua mỗi khách, hàng chỉ bán tại cửa hàng). Listener trả về list<string> lỗi; có lỗi thì từ chối (422 cart.line_rejected). Chạy trong transaction, không I/O mạng.',
    ],
];
