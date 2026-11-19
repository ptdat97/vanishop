<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'media_webp_service_provider' => [
        'also_convert_png_images' => 'Chuyển cả ảnh PNG',
        'default_turn_off_if_png' => 'Mặc định bật. Tắt nếu ảnh PNG (logo, ảnh nền trong suốt) bị lệch màu sau khi chuyển.',
        'leave_empty_use_vanishop_media' => 'Để trống = theo vanishop.media.cache.quality. Đổi xong chạy php artisan vani:media:cache --clear.',
        'webp_quality_1_100' => 'Chất lượng WebP (1–100)',
    ],
];
