<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'order_returns_panel' => [
        'approve_return_exchange' => 'Approve return/exchange',
        'complete_create_exchange_order' => 'Complete & create exchange order',
        'complete_exchange_create_exchange_order' => 'Complete the exchange and create the exchange order?',
        'complete_refund' => 'Complete & refund',
        'create_return_exchange_request' => 'Create a return/exchange request',
        'customer_shipped_goods' => 'Customer shipped the goods',
        'exchange' => ' (exchange)',
        'minus_damage_fees_if_any' => '; minus damage fees if any)',
        'note' => 'Note',
        'open_return_exchange_request' => 'Open return/exchange request',
        'receive_return_restock_every_line' => 'Receive the return and restock every line? (Damaged goods: declare them on the request page.)',
        'received_still_sellable' => 'Received (still sellable)',
        'record_customer_shipped_goods_back' => 'Record that the customer shipped the goods back?',
        'refund_amount_most' => 'Refund amount (at most',
        'reject' => 'Reject',
        'rejection_reason_sent_customer' => 'Rejection reason (sent to the customer)',
        'return_exchange' => 'Return/exchange',
    ],
    'return_controller' => [
        'approve' => 'Approve',
        'received_damaged_not_restocked' => 'Received (damaged, not restocked)',
        'received_still_sellable' => 'Received (still sellable)',
    ],
    'return_dashboard_tasks' => [
        'returns_exchanges_awaiting_approval' => 'Returns/exchanges awaiting approval',
        'returns_received_awaiting_completion' => 'Returns received, awaiting completion',
    ],
    'returns_service_provider' => [
        'approve_reject_receive_returns' => 'Approve, reject, receive returns',
        'complete_returns_exchanges_refund' => 'Complete returns/exchanges and refund',
        'return_exchange' => 'Return/exchange',
        'return_policy' => 'Return policy',
        'returnpolicy_applied_store_s_orders' => 'ReturnPolicy applied to the store\'s orders.',
        'view_return_exchange_requests' => 'View return/exchange requests',
    ],
    'settings' => [
        'attachments_retention_days' => 'Keep return photos (days)',
        'attachments_retention_days_help' => 'Photos attached to return requests are deleted this many days after the request closes.',
    ],
];
