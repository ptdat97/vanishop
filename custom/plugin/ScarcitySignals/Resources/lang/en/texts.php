<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'scarcity_signals_service_provider' => [
        'based_total_sellable_quantity_every' => 'Based on the total sellable quantity of every size and the shared threshold.',
        'low_stock_badge_product_cards' => '"Low stock" badge on product cards',
        'low_stock_threshold_1_100' => '"Low stock" threshold (1–100)',
        'one_category_threshold_per_line' => 'One "category = threshold" per line; the category is a path or id, e.g. "ao-thun = 10", "vay-cuoi = 1". Applies to the product\'s primary category and its parents; with several thresholds the smallest wins.',
        'only_orders_paid_handed_carrier' => 'Only orders that are paid or handed to the carrier; recalculated hourly.',
        'only_show_sold_from_1' => 'Only show "Sold" from (1–1000)',
        'per_category_thresholds' => 'Per-category thresholds',
        'show_only_n_left_product' => 'Show "Only N left" on the product page',
        'show_sold_n_x_days' => 'Show "Sold N in X days"',
        'size_customer_choosing_only_when' => 'For the size the customer is choosing, only when the sellable quantity is at or below the threshold.',
        'sold_1_counter_evidence_almost' => '"Sold 1" is counter-evidence (almost nobody bought it) — below this level it is hidden, without inflating the number.',
        'statistics_window_days_1_90' => 'Statistics window (days, 1–90)',
    ],
];
