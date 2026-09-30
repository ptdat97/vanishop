<?php

/*
 * Cấu hình plugin vani.search-meilisearch. Giữ tên biến env cũ (MEILISEARCH_*) để chuyển từ Core sang plugin
 * không cần đổi .env. Chọn provider bằng VANI_SEARCH_PROVIDER=meilisearch.
 */

return [
    'host' => env('MEILISEARCH_HOST', 'http://127.0.0.1:7700'),
    'key' => env('MEILISEARCH_KEY'),
    'index' => env('MEILISEARCH_INDEX', 'vani_products'),
];
