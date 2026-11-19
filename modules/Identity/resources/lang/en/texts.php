<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'identity_service_provider' => [
        'access_admin_area' => 'Access the admin area',
        'manage_staff_roles' => 'Manage staff and roles',
        'staff' => 'Staff',
    ],
    'role_presets' => [
        'analyst' => [
            'description' => 'View only: orders, customers, products, stock, prices, promotions, reports; Excel export. Customer contacts are masked.',
            'name' => 'Data analyst',
        ],
        'content' => [
            'description' => 'Products, categories, media, pages, posts, storefront design; SEO and Appearance settings. No price changes.',
            'name' => 'Content',
        ],
        'marketing' => [
            'description' => 'Promotions, vouchers, customer segments, message templates, reports; marketing plugin settings. No customer phone/email.',
            'name' => 'Marketing',
        ],
        'order_ops' => [
            'description' => 'Confirm, pack, ship, returns, confirm payments, adjust stock; sees customer contacts. No refunds.',
            'name' => 'Order operations',
        ],
    ],
    'staff_password_controller' => [
        'reset_password' => 'Reset password',
        'set_first_password' => 'Set your first password',
    ],
    'staff_request' => [
        'scope' => 'scope',
    ],
];
