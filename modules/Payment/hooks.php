<?php

/*
| Hook công khai của module Payment. Danh mục tổng: docs/04-extension/extension-point-catalog.md
*/

return [
    'vani.service.payment.view' => [
        'type' => 'filter',
        'visibility' => 'public',
        'since' => '0.3.48',
        'on_error' => 'skip',
        'args' => ['view' => 'Modules\\Payment\\Contracts\\Data\\PaymentView', 'payment' => 'Modules\\Payment\\Persistence\\Models\\Payment'],
        'description' => 'Thanh toán trình bày cho khách (trang thanh toán, sau đặt hàng): đổi/thêm hướng dẫn trong action (PaymentInitiation). Chỉ hiển thị: không đổi số tiền, trạng thái; khởi tạo/IPN của cổng làm qua PaymentGateway.',
    ],
];
