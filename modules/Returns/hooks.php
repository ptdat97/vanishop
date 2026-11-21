<?php

/*
| Hook công khai của module Returns. Danh mục tổng: docs/04-extension/extension-point-catalog.md
*/

return [
    'vani.service.returns.view' => [
        'type' => 'filter',
        'visibility' => 'public',
        'since' => '0.3.48',
        'transaction' => true,
        'on_error' => 'skip',
        'args' => ['view' => 'Modules\\Returns\\Contracts\\Data\\ReturnView', 'return' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest'],
        'description' => 'Yêu cầu đổi/trả trình bày cho Admin và khách. Chỉ hiển thị: số tiền hoàn và trạng thái do Returns quản lý.',
    ],
    'vani.returns.before_approve' => [
        'type' => 'validate',
        'visibility' => 'public',
        'since' => '0.4.60',
        'transaction' => true,
        'args' => ['return' => 'Modules\\Returns\\Contracts\\Data\\ReturnView'],
        'description' => 'Chặn duyệt yêu cầu đổi/trả (chốt ③ ADR-046, EP v3 X2): CSKH duyệt từng yêu cầu, duyệt hàng loạt, và tự duyệt của ReturnPolicy (bị chặn thì chờ CSKH). Listener trả list<string> lý do (vd. khách chưa gửi ảnh). Chỉ đọc, không I/O mạng chậm.',
    ],
];
