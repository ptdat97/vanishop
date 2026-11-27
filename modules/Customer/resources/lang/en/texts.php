<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'account_lifecycle' => [
        'customer_cannot_merged_itself' => 'A customer cannot be merged with itself.',
        'only_two_active_customers_merged' => 'Only two active customers can be merged.',
    ],
    'customer_controller' => [
        'target_customer_not_found' => 'Target customer not found.',
    ],
    'customer_service_provider' => [
        'anonymise_customers_deletion_requests' => 'Anonymise customers on deletion requests',
        'customers' => 'Customers',
        'manage_customer_groups_assign_groups' => 'Manage customer groups and assign groups/tags to customers',
        'merge_duplicate_customers' => 'Merge duplicate customers',
        'otp_delivery_channel' => 'OTP delivery channel',
        'view_customers_owner_level' => 'View customers (Owner level)',
    ],
    'order_customer_notes_panel' => [
        'add_customer_service_note' => 'Add customer service note',
        'customer_profile' => 'Customer profile',
        'customer_service_notes' => 'Customer service notes',
        'no_notes_yet' => 'No notes yet',
        'note_customer_pick_up_after' => 'Note (e.g. customer will pick up after 6pm)',
    ],
    'registration_verification' => [
        'label' => 'Verification when customers sign up',
        'none' => 'None — create the account once all details are filled in',
        'email' => 'Email — send a verification link',
        'otp' => 'OTP — send a code to the phone number (needs an SMS/Zalo ZNS plugin)',
        'help' => 'Customers enter name, email, phone and password. If the email or phone matches a customer who already ordered, verification is always required before the order history is claimed. Choosing OTP without a plugin that can reach phones falls back to the email link.',
    ],
];
