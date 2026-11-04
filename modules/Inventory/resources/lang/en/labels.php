<?php

/*
| Nhãn hiển thị trong Admin (StatusBadge, Select) theo mã; mã chưa có nhãn hiển thị nguyên mã.
*/

return [
    'location_type' => ['warehouse' => 'Warehouse', 'store' => 'Store', 'virtual' => 'Virtual (dropship, partner)'],
    'location_status' => ['active' => 'Active', 'inactive' => 'Inactive'],
    'stock_action' => ['adjust' => 'Add/remove stock', 'count' => 'Stock count (set counted)', 'safety' => 'Safety stock'],
    'transfer_status' => ['pending' => 'Pending', 'shipped' => 'In transit', 'received' => 'Received', 'cancelled' => 'Cancelled'],
    'discrepancy' => ['external_mismatch' => 'External ≠ VaniShop', 'reserved_mismatch' => 'Holds ≠ reserved', 'reserved_off_ledger' => 'Reserved off ledger', 'on_hand_off_ledger' => 'On hand off ledger', 'negative_on_hand' => 'Negative on hand'],
    'resolution' => ['repaired' => 'Reserved repaired', 'applied' => 'Source applied', 'rejected' => 'Rejected — see note'],
];
