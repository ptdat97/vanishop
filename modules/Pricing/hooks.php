<?php

/*
| Hook công khai của module Pricing. Danh mục tổng: docs/04-extension/extension-point-catalog.md
*/

return [
    'vani.service.pricing.prices' => [
        'type' => 'filter',
        'visibility' => 'public',
        'since' => '0.3.47',
        'args' => ['prices' => 'array<int, Modules\\Pricing\\Contracts\\Data\\ResolvedPrice>', 'context' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext'],
        'description' => 'Giá hiệu lực theo variant id sau pricing strategy — một nguồn cho storefront, giỏ và checkout. Chỉ thay ResolvedPrice của variant có sẵn, không thêm/xoá khoá. Gọi rất nhiều lần: không truy vấn DB theo từng variant, không I/O mạng. Quy tắc giá cả cửa hàng nên làm PricingStrategy.',
    ],
];
