<?php

/*
| Nhãn hiển thị trong Admin (StatusBadge, Select) theo mã; mã chưa có nhãn hiển thị nguyên mã.
*/

return [
    'product_status' => ['draft' => 'Nháp', 'active' => 'Đang bán', 'archived' => 'Lưu trữ'],
    'option' => ['color' => 'Màu', 'size' => 'Size'],
    'option_display' => ['swatch' => 'Ô màu', 'button' => 'Nút', 'dropdown' => 'Danh sách thả xuống'],
    'variant_status' => ['active' => 'Đang bán', 'inactive' => 'Ngừng bán'],
    'visibility' => ['active' => 'Hiển thị', 'hidden' => 'Ẩn'],
    'attribute_kind' => ['spec' => 'Hiển thị & lọc (spec)', 'internal' => 'Chỉ nội bộ (internal)'],
    // Bộ lọc storefront (§3.5-1): tên facet + nhóm màu, theo ngôn ngữ trang.
    'facet' => ['brand' => 'Thương hiệu', 'color' => 'Màu', 'size' => 'Kích cỡ', 'price' => 'Giá'],
    'facet_display' => ['list' => 'Danh sách', 'swatch' => 'Ô màu', 'range' => 'Khoảng giá (từ – đến)'],
    'color_family' => [
        'white' => 'Trắng', 'black' => 'Đen', 'grey' => 'Xám', 'beige' => 'Be', 'brown' => 'Nâu', 'red' => 'Đỏ', 'pink' => 'Hồng',
        'orange' => 'Cam', 'yellow' => 'Vàng', 'green' => 'Xanh lá', 'blue' => 'Xanh dương', 'purple' => 'Tím', 'multi' => 'Nhiều màu', 'metallic' => 'Ánh kim',
    ],
];
