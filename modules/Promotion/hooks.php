<?php

/*
| Hook công khai của module Promotion. Danh mục tổng: docs/04-extension/extension-point-catalog.md
*/

return [
    'vani.service.promotion.result' => [
        'type' => 'filter',
        'visibility' => 'public',
        'since' => '0.3.47',
        'args' => ['result' => 'Modules\\Promotion\\Contracts\\Data\\PromotionResult', 'context' => 'Modules\\Promotion\\Contracts\\Data\\PromotionContext'],
        'description' => 'Khuyến mãi đã áp và voucher bị từ chối sau engine. Khuyến mãi plugin thêm vào phải có promotionId thật (ghi nhận lượt dùng, thu hồi khi huỷ). Điều kiện/hành động mới nên làm PromotionRule/PromotionAction. Chạy trong transaction đặt hàng: không I/O mạng.',
    ],
];
