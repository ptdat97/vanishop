<?php

return [
    'policy' => [
        'not_delivered' => 'The order has not been delivered yet.',
        'window_expired' => 'The return window has passed.',
    ],
    'quantity_exceeded' => 'The return quantity exceeds what can be returned (:allowed left).',
    'transition_invalid' => 'The return cannot move from :from to :to.',
    'refund_exceeds' => 'The refund exceeds the amount calculated for this return.',
    'stale' => 'This return was changed by someone else. Reload the page.',
    'updated' => 'Return updated.',
    'received' => 'Return received.',
    'resolved' => 'Return resolved.',
];
