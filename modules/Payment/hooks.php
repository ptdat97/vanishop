<?php

/*
| Hook công khai của module Payment. Danh mục tổng: docs/04-extension/extension-point-catalog.md
*/

return [
    'vani.service.payment.view' => [
        'type' => 'filter',
        'visibility' => 'public',
        'since' => '0.3.48',
        'transaction' => true,
        'on_error' => 'skip',
        'args' => ['view' => 'Modules\\Payment\\Contracts\\Data\\PaymentView', 'payment' => 'Modules\\Payment\\Persistence\\Models\\Payment'],
        'description' => 'Thanh toán trình bày cho khách (trang thanh toán, sau đặt hàng): đổi/thêm hướng dẫn trong action (PaymentInitiation). Chỉ hiển thị: không đổi số tiền, trạng thái; khởi tạo/IPN của cổng làm qua PaymentGateway.',
    ],
    'vani.payment.before_refund' => [
        'type' => 'validate',
        'visibility' => 'public',
        'since' => '0.4.60',
        'transaction' => true,
        'args' => ['payment' => 'Modules\\Payment\\Contracts\\Data\\PaymentView', 'amount' => 'Modules\\Shared\\Domain\\Money\\Money', 'reason' => 'string'],
        'description' => 'Chặn hoàn tiền do nhân viên tạo (EP v3 X2) — vd. trên mức tiền cần quản lý duyệt. Hoàn tự động (huỷ đơn, thanh toán muộn sau huỷ) không qua hook này. Listener trả list<string> lý do. Chỉ đọc, không I/O mạng chậm.',
    ],
];
