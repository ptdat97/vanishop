<?php

return [
    'external_authority' => 'Physical stock at this location is managed by [:authority]; manual adjustments are not allowed.',
    'stale' => 'This record was changed by someone else. Reload the page.',
    'saved' => 'Saved.',
    'adjusted' => 'Stock updated.',

    'transfer_created' => 'Stock transfer created.',
    'transfer_shipped' => 'Goods shipped; stock at the source location has been deducted.',
    'transfer_received' => 'Goods received; stock at the destination location has been added.',
    'transfer_cancelled' => 'Stock transfer cancelled.',
    'transfer_no_lines' => 'A transfer must have at least one line.',
    'transfer_same_location' => 'From and to locations must be different.',
    'transfer_external_authority' => 'Transfers are only allowed between locations whose physical stock VaniShop manages.',
    'transfer_invalid_transition' => 'Cannot move the transfer from [:from] to [:to].',
    'transfer_received_range' => 'Received quantity for variant #:variant must be between 0 and :max.',
    'transfer_unknown_sku' => 'SKU [:sku] was not found.',
];
