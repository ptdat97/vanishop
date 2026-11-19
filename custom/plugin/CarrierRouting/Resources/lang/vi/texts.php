<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'carrier_routing_service_provider' => [
        'carrier_selection_rules' => 'Quy tắc chọn hãng',
        'one_condition_carrier_service_per' => 'Mỗi dòng "điều kiện = hãng[:dịch vụ]", dòng đầu tiên khớp thắng. Điều kiện: "tinh:79,01" (mã tỉnh/thành), "cod>=3000000" (thu hộ từ mức), "*" (mọi đơn). Vd. "tinh:79 = ahamove".',
    ],
    'routing_rules' => [
        'carrier_selection_rules' => 'Quy tắc chọn hãng: ',
    ],
];
