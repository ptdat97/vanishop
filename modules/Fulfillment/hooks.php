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
];
