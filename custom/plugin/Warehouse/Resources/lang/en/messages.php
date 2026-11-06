<?php

// Chuỗi Admin của vani.warehouse (ADR-042).
return [
    'stale' => 'This record was changed by someone else. Reload the page.',
    'saved' => 'Saved.',
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
    'transfers_in_transit' => ':count stock transfers are in transit — receive or cancel them before disabling Warehouse operations.',
];
