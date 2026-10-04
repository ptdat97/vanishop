<?php

return [
    'transition_invalid' => 'The order cannot move from :from to :to.',
    'cannot_cancel' => 'The order is already being processed or shipped and cannot be cancelled.',
    'cannot_change_address' => 'The order is already shipping or closed; the address cannot be changed.',
    'stale' => 'This order was changed by someone else. Reload the page.',
    'not_found' => 'Order not found.',
    'confirmed' => 'Order confirmed.',
    'cancelled' => 'Order cancelled.',
    'cannot_cancel_lines' => 'Partial cancellation needs a confirmed order not yet shipped, paid or cash-on-delivery pending.',
    'invalid_cancel_quantities' => 'Invalid cancel quantities (exceeds remaining, line not in order, or all lines — cancel the whole order instead).',
    'lines_cancelled' => 'Order partially cancelled.',
    'address_invalid' => 'Invalid province or ward for the current administrative list.',
    'address_changed' => 'Shipping address changed.',
    'note_added' => 'Note added.',
];
