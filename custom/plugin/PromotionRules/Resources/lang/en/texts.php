<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'customer_has_tags_rule' => [
        'customer_has_tag' => 'Customer has tag',
        'enter_least_one_tag_tags' => 'Enter at least one tag (tags).',
        'match_must_any_all' => 'match must be any or all.',
    ],
    'first_order_only_rule' => [
        'first_order_only' => 'First order only',
        'rule_takes_no_settings' => 'This rule takes no settings.',
    ],
    'in_brands_rule' => [
        'brand' => 'In brand',
        'every_slug_slugs_must_non' => 'Every slug in "slugs" must be a non-empty string.',
        'non_empty_slugs_list_required' => 'A non-empty "slugs" list is required.',
    ],
    'in_collections_rule' => [
        'collection' => 'In collection',
        'every_slug_slugs_must_non' => 'Every slug in "slugs" must be a non-empty string.',
        'non_empty_slugs_list_required' => 'A non-empty "slugs" list is required.',
    ],
    'in_customer_groups_rule' => [
        'choose_least_one_customer_group' => 'Choose at least one customer group (groups).',
        'customer_group' => 'Customer in group',
        'customer_group_not_found' => 'Customer group not found:',
    ],
    'min_order_subtotal_rule' => [
        'buy_more' => 'Add :amount more to use this offer.',
        'min_subtotal_must_positive_number' => '"min_subtotal" must be a positive number.',
        'minimum_order_value' => 'Minimum order value',
    ],
    'min_quantity_rule' => [
        'buy_more' => 'Add :count more items to use this offer.',
        'min_quantity_must_positive_integer' => '"min_quantity" must be a positive integer.',
        'minimum_product_quantity' => 'Minimum product quantity',
    ],
    'in_products_rule' => [
        'product' => 'In products',
        'product_ids_required' => 'Choose at least one product (product_ids).',
    ],
    'in_categories_rule' => [
        'category' => 'In categories',
        'category_ids_required' => 'Choose at least one category (category_ids).',
    ],
    'exclude_on_sale_rule' => [
        'exclude_on_sale' => 'Exclude items already on sale',
    ],
];
