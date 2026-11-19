<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'cod_risk_guard_service_provider' => [
        'block_confirming_orders_from_blacklisted' => 'Block confirming orders from blacklisted phone numbers',
        'one_number_per_line_0912345678' => 'One number per line, e.g. 0912345678. COD orders from these numbers are flagged high risk.',
        'order_number_was_returned_sender' => 'Order :number was returned to sender (the customer did not accept it).',
        'phone_blacklist' => 'Phone blacklist',
        'phone_number_store_s_blacklist' => 'The phone number is on the store\'s blacklist — call the customer to confirm, then remove the number from the blacklist (Settings → COD fraud protection)',
        'returned_orders_count_high_risk' => 'Returned orders to count as high risk',
        'staff_must_remove_number_from' => 'Staff must remove the number from the blacklist (after calling to confirm) before confirming.',
        'tag_bom_hang_add_customer' => 'Tag "bom-hang" and add a customer service note when a customer\'s order is returned to sender',
    ],
    'risk_rules' => [
        'blacklisted_phone_number' => 'Blacklisted phone number',
        'phone_number_already_has_returned' => 'The phone number already has :returned orders returned to sender (threshold :threshold).',
        'phone_number_store_s_blacklist' => 'The phone number is on the store\'s blacklist.',
        'returned_sender_returned_times' => 'Returned to sender :returned times',
    ],
];
