<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'scarcity_signals_service_provider' => [
        'based_total_sellable_quantity_every' => 'Theo tổng số bán được của mọi size và ngưỡng chung.',
        'low_stock_badge_product_cards' => 'Nhãn "Sắp hết" ở thẻ sản phẩm',
        'low_stock_threshold_1_100' => 'Ngưỡng "sắp hết" (1–100)',
        'one_category_threshold_per_line' => 'Mỗi dòng "danh mục = ngưỡng", danh mục là đường dẫn hoặc id, vd. "ao-thun = 10", "vay-cuoi = 1". Áp cho danh mục chính và danh mục cha của sản phẩm; nhiều ngưỡng thì lấy nhỏ nhất.',
        'only_orders_paid_handed_carrier' => 'Chỉ đơn đã thu tiền hoặc đã giao cho hãng vận chuyển; tính lại mỗi giờ.',
        'only_show_sold_from_1' => 'Chỉ hiện "Đã bán" khi từ (1–1000)',
        'per_category_thresholds' => 'Ngưỡng riêng theo danh mục',
        'show_only_n_left_product' => 'Hiện "Chỉ còn N" ở trang sản phẩm',
        'show_sold_n_x_days' => 'Hiện "Đã bán N trong X ngày"',
        'size_customer_choosing_only_when' => 'Theo đúng size khách đang chọn, chỉ khi số bán được từ ngưỡng trở xuống.',
        'sold_1_counter_evidence_almost' => '"Đã bán 1" là bằng chứng ngược (gần như không ai mua) — dưới mức này thì giấu, không làm đẹp số.',
        'statistics_window_days_1_90' => 'Cửa sổ thống kê (ngày, 1–90)',
    ],
];
