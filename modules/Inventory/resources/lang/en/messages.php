<?php

return [
    'external_authority' => 'Physical stock at this location is managed by [:authority]; manual adjustments are not allowed.',
    'stale' => 'This record was changed by someone else. Reload the page.',
    'saved' => 'Saved.',
    'batch_reason' => 'Stock update',
    'bulk_reason' => 'Bulk stock update',
    'bulk_done' => 'At :location: changed :updated SKUs, :unchanged unchanged.',
    'bulk_skipped' => ':count SKUs skipped (e.g. removing more than on hand).',
    'bulk_negative' => 'Quantity cannot be negative (only "Add/remove" accepts negatives).',
    'batch_saved' => 'Updated :updated stock cells.',
    'batch_conflicts' => ':count cells were not saved because stock changed after the page was opened (e.g. just shipped) — reloaded the new numbers.',
    'default_created' => 'Created warehouse :name. You can now enter stock.',
    'default_exists' => 'The store already has a location.',
    'import_reason' => 'CSV import :file',
    'import_applied' => 'Imported stock: :updated rows.',
    'import_conflicts' => ':count rows skipped because stock changed after the preview.',
    'import_nothing' => 'The file has no rows to change.',
    'import_expired' => 'The import session expired (30 minutes). Please upload the file again.',
    'import_unreadable' => 'Could not read the file.',
    'import_header' => 'The first row must have the sku and on_hand columns (location optional).',
    'import_too_many' => 'At most :max rows per import.',
    'adjusted' => 'Stock updated.',

    'location_missing' => 'The location does not exist.',
    'transfer_external_authority' => 'Transfers are only allowed between locations whose physical stock VaniShop manages.',

    'reconcile_source_required' => '--source is required and must not be "vanishop".',
    'reconcile_input_required' => 'Provide either --file or --json with the snapshot.',
    'reconcile_invalid_json' => 'Invalid snapshot JSON.',
];
