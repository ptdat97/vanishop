<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'preorder_service_provider' => [
        'arrival_date_sku_invalid_use' => 'The arrival date of :sku is invalid — use dd/mm/yyyy.',
        'arriving' => ' · arriving',
        'awaiting_stock' => ' · awaiting stock',
        'awaiting_stock_2' => 'awaiting stock',
        'csv_xlsx_file_column_1' => '.csv/.xlsx file: column 1 SKU, column 2 incoming quantity (0 = delete), column 3 expected date dd/mm/yyyy (optional).',
        'do_not_hand_orders_still' => 'Do not hand orders still waiting for stock to the carrier',
        'expected' => 'expected',
        'overdue_expected' => 'Past expected date :date',
        'file_has_no_matching_sku' => 'The file has no matching SKU in the first column.',
        'import_incoming_stock' => 'Import incoming stock',
        'incoming' => 'Incoming',
        'not_found' => ' Not found:',
        'open_pre_orders_what_incoming' => 'Open pre-orders for what % of incoming stock (0–100)',
        'pre_order_awaiting_stock' => 'Pre-order — awaiting stock',
        'pre_orders_past_their_expected' => 'Pre-orders past their expected date (SKUs with waiting orders)',
        'receive_stock_when_arrives_then' => ' — receive the stock when it arrives, then hand it to the carrier, or use "Ship available items first" on the order page',
        'shipment_has_items_awaiting_stock' => 'Shipment has items awaiting stock:',
        'text_50_40_units_incoming_20' => 'E.g. 50: with 40 units incoming, 20 pre-order slots open. 0 = pause pre-orders; "Incoming" still shows in Inventory.',
        'updated_incoming_stock_saved_skus' => 'Updated incoming stock for :saved SKUs.',
    ],
];
