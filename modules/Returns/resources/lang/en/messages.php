<?php

return [
    'bulk_approved' => 'Approved :count requests; skipped :skipped no longer awaiting approval.',
    'bulk_received' => 'Received :count returns (restocked); skipped :skipped not approved or already received.',
    'policy' => [
        'waived' => 'The customer confirmed receipt and waived returns for this order.',
        'not_delivered' => 'The order has not been delivered yet.',
        'window_expired' => 'The return window has passed.',
        'exchange_order' => 'A replacement order can only be exchanged again for another size/colour of the same product. Please contact support to return it for a refund or switch product.',
    ],
    'quantity_exceeded' => 'The return quantity exceeds what can be returned (:allowed left).',
    'transition_invalid' => 'The return cannot move from :from to :to.',
    'refund_exceeds' => 'The refund exceeds the amount calculated for this return.',
    'unknown_lines' => 'Item condition refers to lines outside this return request.',
    'stale' => 'This return was changed by someone else. Reload the page.',
    'created' => 'Return request created.',
    'updated' => 'Return updated.',
    'received' => 'Return received.',
    'resolved' => 'Return resolved.',
    'exchange_invalid' => [
        'lines' => 'An exchange needs a replacement product for every returned line.',
        'variant' => 'The replacement product does not exist or is no longer sold.',
    ],
    'exchange_unavailable' => 'Could not create the replacement order: :reason',
    'exchanged' => 'Exchange completed, replacement order :number created.',
    'reason_invalid' => 'The return reason is invalid or no longer available.',
    'reason_name_required' => 'A name in the default language is required.',
    'reason_code_locked' => 'This reason is in use: its code cannot change, only its name or status.',
    'reason_in_use' => 'This reason is used by return requests: it can be disabled, not deleted.',
    'reason_saved' => 'Reason saved.',
    'reason_deleted' => 'Reason deleted.',
    'attachments' => ['too_many' => 'Up to :max photos per request.', 'invalid_file' => 'Photos must be JPEG, PNG or WebP, up to 5 MB.', 'closed' => 'This request has been processed; photos can no longer be added.'],
];
