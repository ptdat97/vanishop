<?php

return [
    'reconciliations_resolved' => 'Resolved :count reconciliation entries and skipped :skipped that were no longer open.',
    'gateway_unavailable' => 'This payment method is not available.',
    'not_found' => 'Payment not found.',
    'invalid_state' => 'The payment is :status; this action is not allowed.',
    'refund_exceeds' => 'The refund exceeds the refundable amount (:amount).',
    'initiate_failed' => 'Could not reach the payment gateway. Please retry or choose another method.',
    'confirmed' => 'Payment confirmed.',
    'refund_created' => 'Refund requested.',
    'refund_completed' => 'Refund recorded.',
    'capture_failed' => 'The gateway refused to capture the authorized amount. Check with the gateway or contact the customer.',
    'captured' => 'Payment captured.',
    'expired' => 'The payment window for this order has expired.',
    'cod_collected' => 'Cash on delivery recorded as collected.',
    'refund_blocked' => 'Refund cannot be created yet: :reasons',
    'payout_failed' => 'Payout via :channel failed: :message',
    'payout_done' => 'Paid out via :channel (ref :reference).',
    'reconciliation_resolved' => 'Reconciliation line marked as resolved.',
];
