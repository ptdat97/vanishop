<?php

/*
| Hook công khai của module Fulfillment. Danh mục tổng: docs/04-extension/extension-point-catalog.md
*/

return [
    'vani.service.fulfillment.shipment_data' => [
        'type' => 'filter',
        'visibility' => 'public',
        'since' => '0.3.47',
        'args' => ['data' => 'Modules\\Fulfillment\\Contracts\\Data\\ShipmentData', 'shipment' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment'],
        'description' => 'Dữ liệu vận đơn gửi cho hãng vận chuyển (tạo/huỷ vận đơn). Không đổi codAmount: số tiền thu hộ đã chốt theo đơn và đối soát thanh toán.',
    ],
    'vani.fulfillment.before_push' => [
        'type' => 'validate',
        'visibility' => 'public',
        'since' => '0.4.60',
        'args' => ['shipment' => 'Modules\\Fulfillment\\Contracts\\Data\\ShipmentData'],
        'description' => 'Chặn đẩy vận đơn lên hãng (chốt ② ADR-046, EP v3 X2): nút, hàng loạt, booking = on_create, plugin gọi ShipmentBooking, và nhập mã vận đơn tay. Listener trả list<string> lý do (vd. thiếu khối lượng, phường chưa chuẩn hoá); vận đơn giữ "Chờ đóng gói". Chỉ đọc, không I/O mạng chậm.',
    ],
];
