<?php

/*
| Nhãn hiển thị trong Admin (StatusBadge, Select) theo mã; mã chưa có nhãn hiển thị nguyên mã.
*/

return [
    'price_list_type' => ['base' => 'Giá niêm yết (base)', 'sale' => 'Khuyến mãi (sale)', 'member' => 'Thành viên (member)'],
    'price_list_status' => ['active' => 'Đang dùng', 'inactive' => 'Tắt'],
    'history' => ['system' => 'Hệ thống', 'staff_removed' => 'Nhân viên đã xoá'],
    'matrix' => ['label' => 'Giá', 'price' => 'Giá bán (niêm yết)', 'compare_at' => 'Giá so sánh', 'compare_at_help' => 'Giá gạch ngang; để trống nếu không giảm.'],
    'price_list_state' => ['running' => 'Đang chạy', 'scheduled' => 'Sắp chạy', 'expired' => 'Đã hết hạn', 'disabled' => 'Tắt'],
    'bulk' => [
        'mode' => 'Cách tính', 'value' => 'Giá trị',
        'modes' => ['percent' => 'Giảm %', 'amount' => 'Giảm số tiền', 'fixed' => 'Đồng giá'],
        'warnings' => ['no_base' => 'Chưa có giá niêm yết — bỏ qua', 'zero' => 'Giá 0đ', 'not_lower' => 'Không thấp hơn giá niêm yết', 'over_cap' => 'Giảm vượt mức trần'],
    ],
];
