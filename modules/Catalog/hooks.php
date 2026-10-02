<?php

/*
| Hook công khai của module Catalog. Danh mục tổng: docs/04-extension/extension-point-catalog.md
*/

return [
    'vani.product.before_save' => [
        'type' => 'validate',
        'visibility' => 'public',
        'since' => '0.1',
        'args' => ['draft' => 'Modules\\Catalog\\Contracts\\Data\\ProductDraft'],
        'description' => 'Kiểm tra quy tắc riêng trước khi lưu sản phẩm. Listener trả về list<string> lỗi; có lỗi thì không lưu.',
    ],
    'vani.product.after_save' => [
        'type' => 'action',
        'visibility' => 'public',
        'since' => '0.1',
        'args' => ['styleId' => 'int'],
        'description' => 'Chạy TRONG transaction lưu sản phẩm: chỉ ghi DB của plugin, không I/O mạng.',
    ],
    'vani.catalog.listing.query' => [
        'type' => 'filter',
        'visibility' => 'public',
        'since' => '0.3',
        'args' => ['query' => 'Modules\\Catalog\\Contracts\\Data\\ProductSearchQuery'],
        'description' => 'Sửa truy vấn danh sách/tìm kiếm sản phẩm storefront (merchandising: lọc, sắp xếp). Phải trả về ProductSearchQuery; kiểu khác bị bỏ qua.',
    ],
];
