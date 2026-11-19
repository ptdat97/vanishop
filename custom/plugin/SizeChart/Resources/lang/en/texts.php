<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'size_chart_controller' => [
        'image_no_longer_media_library' => 'The image is no longer in the Media library.',
        'size_chart_deleted_every_assignment' => 'Size chart deleted and every assignment removed.',
        'size_chart_saved' => 'Size chart saved.',
    ],
    'size_chart_service_provider' => [
        'applies_brand_s_products_when' => 'Applies to the brand\'s products when neither the product nor its category has its own chart.',
        'applies_products_whose_primary_category' => 'Applies to products whose primary category is this category or a subcategory. Leave empty = use the parent category, brand or default chart.',
        'leave_empty_use_category_brand' => 'Leave empty = use the category, brand or default chart.',
        'manage_size_charts' => 'Manage size charts',
        'size_charts' => 'Size charts',
        'view_size_charts' => 'View size charts',
    ],
    'size_charts' => [
        'off' => ' (off)',
        'size_chart_was_just_edited' => 'The size chart was just edited by someone else. Reload the page to see the new version.',
    ],
];
