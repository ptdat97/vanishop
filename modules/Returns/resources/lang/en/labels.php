<?php

/*
| Nhãn hiển thị trong Admin (StatusBadge, Select) theo mã; mã chưa có nhãn hiển thị nguyên mã.
*/

return [
    'return_status' => ['requested' => 'Requested', 'approved' => 'Approved', 'in_transit' => 'In transit', 'received' => 'Received', 'resolved' => 'Resolved', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled'],
    'reason' => ['wrong_size' => 'Wrong size', 'not_as_described' => 'Not as described', 'defective' => 'Defective', 'changed_mind' => 'Changed mind', 'other' => 'Other'],
    'condition' => ['sellable' => 'Sellable (restock)', 'damaged' => 'Damaged (no restock)'],
];
