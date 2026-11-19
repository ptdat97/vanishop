<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'order_payment_panel' => [
        'awaiting_payout' => ' (awaiting payout',
        'capture' => 'Capture',
        'cash_collected' => 'Cash collected',
        'cod_collected' => 'COD collected',
        'confirm_money_received' => 'Confirm money received',
        'confirm_payment' => 'Confirm payment',
        'gateway_reported_result' => 'Gateway reported the result',
        'no_payments_yet' => 'No payments yet',
        'note_bank_transaction_id_checked' => 'Note (bank transaction ID, checked by…)',
        'note_collected_store_delivered_itself' => 'Note (collected by, store delivered itself, carrier has not reported delivery…)',
        'note_refund_transfer_transaction_id' => 'Note (refund transfer transaction ID)',
        'payment' => 'Payment',
        'payment_2' => 'Payment:',
        'payment_management' => 'Payment management',
        'reason' => 'Reason',
        'refund' => 'Refund',
        'refund_amount_most' => 'Refund amount (at most',
        'refund_paid' => 'Refund paid',
        'refunded' => 'Refunded',
    ],
    'payment_dashboard_tasks' => [
        'reconciliation_mismatches' => 'Reconciliation mismatches',
        'refund_awaiting_payout' => 'Refund awaiting payout',
        'statement_does_not_match_order' => 'Statement does not match the order',
    ],
    'payment_service_provider' => [
        'confirm_money_received_manual_bank' => 'Confirm money received (manual bank transfer)',
        'no_cash_delivery_gateway_collectsondelivery' => 'No cash-on-delivery gateway (collectsOnDelivery) is accepting new transactions — exchanges requiring a top-up will be rejected. Enable vani.cod or a similar gateway.',
        'open_payments_still_pending_authorised' => ':open payments are still pending/authorised via gateway :code',
        'payment' => 'Payment',
        'payment_gateways' => 'Payment gateways',
        'refund' => 'Refund',
        'view_payments' => 'View payments',
    ],
    'payment_settlement' => [
        'authorised_amount_does_not_match' => 'Authorised amount does not match the payment',
        'captured_amount_does_not_match' => 'Captured amount does not match the payment',
    ],
    'refund_service' => [
        'pay_out_via_label' => 'Pay out via :label',
    ],
    'settings' => [
        'capture_on' => 'Capture authorized online payments',
        'capture_on_help' => 'For gateways that authorize first (cards): when the customer is actually charged.',
        'capture_on_manual' => 'When staff click "Capture"',
        'capture_on_shipped' => 'When the shipment leaves the warehouse',
    ],
];
