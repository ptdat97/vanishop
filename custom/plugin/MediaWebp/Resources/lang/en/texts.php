<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'media_webp_service_provider' => [
        'also_convert_png_images' => 'Also convert PNG images',
        'default_turn_off_if_png' => 'On by default. Turn off if PNG images (logos, transparent backgrounds) shift colour after conversion.',
        'leave_empty_use_vanishop_media' => 'Leave empty = use vanishop.media.cache.quality. After changing, run php artisan vani:media:cache --clear.',
        'webp_quality_1_100' => 'WebP quality (1–100)',
    ],
];
