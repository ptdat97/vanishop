<?php

/*
| Nhãn hiển thị trong Admin (StatusBadge, Select) theo mã; mã chưa có nhãn hiển thị nguyên mã.
*/

return [
    'price_list_type' => ['base' => 'List price (base)', 'sale' => 'Sale', 'member' => 'Member'],
    'price_list_status' => ['active' => 'Active', 'inactive' => 'Inactive'],
    'history' => ['system' => 'System', 'staff_removed' => 'Removed staff'],
    'matrix' => ['label' => 'Price', 'price' => 'Price (list)', 'compare_at' => 'Compare-at price', 'compare_at_help' => 'Strike-through price; leave empty when not discounted.'],
    'price_list_state' => ['running' => 'Running', 'scheduled' => 'Scheduled', 'expired' => 'Expired', 'disabled' => 'Disabled', 'outside_window' => 'Outside time window'],
    'bulk' => [
        'mode' => 'Method', 'value' => 'Value',
        'modes' => ['percent' => 'Percent off', 'amount' => 'Amount off', 'fixed' => 'Fixed price'],
        'warnings' => ['no_base' => 'No list price — skipped', 'zero' => 'Zero price', 'not_lower' => 'Not below list price', 'over_cap' => 'Over discount cap'],
    ],
];
