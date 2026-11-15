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
    'vani.pricing.bulk.warnings' => [
        'type' => 'filter',
        'visibility' => 'public',
        'since' => '0.4.62',
        'on_error' => 'skip',
        'args' => ['warnings' => 'array', 'variantId' => 'int', 'listAmount' => '?int', 'amount' => 'int', 'priceList' => 'string'],
        'description' => 'Cảnh báo thêm theo SKU trên trang làm việc bảng giá và xem trước "Áp giá…" (EP v3 X4) — vd. "Dưới giá vốn", "Dưới giá tối thiểu thương hiệu". Trả list<string> nhãn cảnh báo; chỉ được thêm (cảnh báo Core luôn giữ); không chặn lưu.',
    ],
];
