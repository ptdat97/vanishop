<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'size_chart_controller' => [
        'image_no_longer_media_library' => 'Ảnh không còn trong Thư viện ảnh.',
        'size_chart_deleted_every_assignment' => 'Đã xoá bảng size và bỏ mọi gán.',
        'size_chart_saved' => 'Đã lưu bảng size.',
    ],
    'size_chart_service_provider' => [
        'applies_brand_s_products_when' => 'Áp dụng cho sản phẩm của thương hiệu khi sản phẩm và danh mục chưa có bảng riêng.',
        'applies_products_whose_primary_category' => 'Áp dụng cho sản phẩm có danh mục chính là danh mục này hoặc danh mục con. Để trống = theo danh mục cha, thương hiệu hoặc bảng mặc định.',
        'leave_empty_use_category_brand' => 'Để trống = theo danh mục, thương hiệu hoặc bảng mặc định.',
        'manage_size_charts' => 'Quản lý bảng size',
        'size_charts' => 'Bảng size',
        'view_size_charts' => 'Xem bảng size',
    ],
    'size_charts' => [
        'off' => ' (tắt)',
        'size_chart_was_just_edited' => 'Bảng size vừa được người khác sửa. Tải lại trang để xem bản mới.',
    ],
];
