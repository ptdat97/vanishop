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
    'vani.service.catalog.search' => [
        'type' => 'filter',
        'visibility' => 'public',
        'since' => '0.3.47',
        'on_error' => 'skip',
        'args' => ['result' => 'array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, facets: array<string, mixed>, filters: list<array<string, mixed>>}', 'filters' => 'Modules\\Catalog\\Contracts\\Data\\ProductFilters', 'locale' => 'string'],
        'description' => 'Kết quả tìm kiếm/danh sách sản phẩm storefront (CatalogReader::searchProducts). Thêm khoá vào item/facet dưới id plugin; không đổi giá (giá do Pricing).',
    ],
    'vani.service.catalog.suggestions' => [
        'type' => 'filter',
        'visibility' => 'public',
        'since' => '0.3.71',
        'on_error' => 'skip',
        'args' => ['result' => 'array{products: list<array<string, mixed>>, categories: list<array<string, mixed>>, brands: list<array<string, mixed>>}', 'text' => 'string', 'locale' => 'string'],
        'description' => 'Gợi ý tìm kiếm (CatalogReader::suggestions — ô tìm kiếm). Thêm khoá dưới id plugin; không thêm nhóm gợi ý mới vào khoá gốc.',
    ],
    'vani.service.catalog.product_detail' => [
        'type' => 'filter',
        'visibility' => 'public',
        'since' => '0.3.47',
        'on_error' => 'skip',
        'args' => ['product' => 'array<string, mixed>', 'slug' => 'string', 'locale' => 'string'],
        'description' => 'Chi tiết sản phẩm storefront (CatalogReader::productDetail), chỉ khi tìm thấy. Thêm khoá dưới id plugin.',
    ],
    'vani.service.catalog.products_by_ids' => [
        'type' => 'filter',
        'visibility' => 'public',
        'since' => '0.3.48',
        'on_error' => 'skip',
        'args' => ['items' => 'list<array<string, mixed>>', 'locale' => 'string'],
        'description' => 'Item catalog theo danh sách id style (CatalogReader::productsByIds — thẻ sản phẩm, bán chạy, đã xem). Giữ thứ tự; thêm khoá dưới id plugin.',
    ],
    'vani.service.catalog.brands' => [
        'type' => 'filter',
        'visibility' => 'public',
        'since' => '0.3.48',
        'on_error' => 'skip',
        'args' => ['brands' => 'list<array<string, mixed>>'],
        'description' => 'Danh sách thương hiệu đang hiển thị trên storefront.',
    ],
    'vani.service.catalog.brand' => [
        'type' => 'filter',
        'visibility' => 'public',
        'since' => '0.3.48',
        'on_error' => 'skip',
        'args' => ['brand' => 'array<string, mixed>', 'slug' => 'string'],
        'description' => 'Trang thương hiệu (khi tìm thấy): thêm khoá dưới id plugin (nội dung, banner).',
    ],
    'vani.service.catalog.category_tree' => [
        'type' => 'filter',
        'visibility' => 'public',
        'since' => '0.3.47',
        'on_error' => 'skip',
        'args' => ['tree' => 'list<array<string, mixed>>', 'locale' => 'string'],
        'description' => 'Cây danh mục storefront đang hiển thị (cũng là nguồn của CatalogReader::category).',
    ],
];
