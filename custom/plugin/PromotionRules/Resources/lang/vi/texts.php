<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'customer_has_tags_rule' => [
        'customer_has_tag' => 'Khách có tag',
        'enter_least_one_tag_tags' => 'Nhập ít nhất một tag (tags).',
        'match_must_any_all' => 'match phải là any hoặc all.',
    ],
    'first_order_only_rule' => [
        'first_order_only' => 'Chỉ áp dụng cho đơn đầu tiên',
        'rule_takes_no_settings' => 'Rule này không nhận cấu hình.',
    ],
    'in_brands_rule' => [
        'brand' => 'Thuộc thương hiệu',
        'every_slug_slugs_must_non' => 'Mỗi slug trong "slugs" phải là chuỗi không rỗng.',
        'non_empty_slugs_list_required' => 'Cần khai báo danh sách slug "slugs" không rỗng.',
    ],
    'in_collections_rule' => [
        'collection' => 'Thuộc bộ sưu tập',
        'every_slug_slugs_must_non' => 'Mỗi slug trong "slugs" phải là chuỗi không rỗng.',
        'non_empty_slugs_list_required' => 'Cần khai báo danh sách slug "slugs" không rỗng.',
    ],
    'in_customer_groups_rule' => [
        'choose_least_one_customer_group' => 'Chọn ít nhất một nhóm khách (groups).',
        'customer_group' => 'Khách thuộc nhóm',
        'customer_group_not_found' => 'Nhóm khách không tồn tại: ',
    ],
    'min_order_subtotal_rule' => [
        'min_subtotal_must_positive_number' => 'Cần khai báo "min_subtotal" là số dương.',
        'minimum_order_value' => 'Giá trị đơn tối thiểu',
    ],
    'min_quantity_rule' => [
        'min_quantity_must_positive_integer' => 'Cần khai báo "min_quantity" là số nguyên dương.',
        'minimum_product_quantity' => 'Số lượng sản phẩm tối thiểu',
    ],
];
