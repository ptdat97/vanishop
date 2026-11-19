<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'carrier_routing_service_provider' => [
        'carrier_selection_rules' => 'Carrier selection rules',
        'one_condition_carrier_service_per' => 'One "condition = carrier[:service]" per line; the first matching line wins. Conditions: "tinh:79,01" (province/city codes), "cod>=3000000" (COD from an amount), "*" (every order). E.g. "tinh:79 = ahamove".',
    ],
    'routing_rules' => [
        'carrier_selection_rules' => 'Carrier selection rules:',
    ],
];
