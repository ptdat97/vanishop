<?php

/*
 * Plugin vani.demo-catalog — nguồn ảnh sản phẩm demo (ảnh chụp sản phẩm của Owner trong VaniCommerce).
 */

return [
    // Thư mục ảnh nguồn (jpg/jpeg/png/webp, chỉ đọc cấp đầu).
    'source' => env('VANI_DEMO_IMAGES', base_path('../VaniCommerce/public/image/catalog/products')),

    // Cạnh dài tối đa khi nhập (px) — ảnh gốc ~8000px; Core tự sinh thêm bản WebP 400/800/1600.
    'max_dimension' => (int) env('VANI_DEMO_IMAGE_MAX', 1600),
];
