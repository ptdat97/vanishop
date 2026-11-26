<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'admin_path' => [
        'format' => 'Use lowercase letters without accents, digits and hyphens only (up to 64 characters), no slashes.',
        'reserved' => 'This path is already used by the store (a page, the API or a language). Choose another one.',
        'nav' => 'Admin path',
    ],
    'identity_service_provider' => [
        'access_admin_area' => 'Access the admin area',
        'audit_archive_months' => 'Archive audit log after (months)',
        'audit_archive_months_help' => 'Audit entries older than this move to the archive table (audit_logs_archive) nightly — never deleted.',
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
];
