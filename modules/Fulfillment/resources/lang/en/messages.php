<?php

return [
    'tracking_imported' => 'Recorded :booked tracking numbers; :unchanged already recorded; :errors rows failed.',
    'bulk_created' => 'Created shipments for :count orders; skipped :skipped not confirmed or already shipped.',
    'bulk_handed_over' => 'Marked :count shipments as handed to the carrier.',
    'pushed' => 'Sent :count shipments to the carrier — tracking numbers arrive shortly; failures show under "Delivery issues". Skipped :skipped already booked or manual-carrier shipments.',
    'manual' => 'Manual shipment',
    'transition_invalid' => 'The shipment cannot move from :from to :to.',
    'order_not_ready' => 'The order is :status; shipments cannot be created yet.',
    'exists' => 'The order already has active shipments.',
    'allocation_mismatch' => 'The allocation does not match the stock reserved for the order.',
    'tracking_taken' => 'This tracking number is already used by another shipment.',
    'created' => 'Shipment created.',
    'booked' => 'Tracking number recorded.',
    'updated' => 'Shipment status updated.',
    'cancelled' => 'Shipment cancelled.',
];
