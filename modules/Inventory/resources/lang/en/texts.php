<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'inventory_service_provider' => [
        'adjust_count_inventory' => 'Adjust/count inventory',
        'below_sellable_quantity_admin_highlights' => 'At or below this sellable quantity: Admin highlights a warning, the Overview counts it under "Low stock", the storefront shows "low stock". 0 = only warn when out of stock.',
        'edit_main_warehouse_details_create' => 'Edit the main warehouse details, create the main warehouse (Owner level)',
        'inventory' => 'Inventory',
        'low_stock_threshold' => '"Low stock" threshold',
        'sellable_quantity_calculation_ats' => 'Sellable quantity calculation (ATS)',
        'sellable_stock_strategy' => 'Sellable stock strategy',
        'strategy_only_reduce_ats_compared' => 'A strategy can only reduce ATS compared with the standard formula.',
        'view_inventory' => 'View inventory',
    ],
    'not_stock_authority' => [
        'not_stock_authority_location_location' => 'Not the stock authority of location :location_code.',
    ],
    'product_list_stock' => [
        'out_stock' => 'Out of stock',
        'sellable_low_stock' => ':sellable · low stock',
        'sellable_stock' => 'Sellable stock',
        'stock_not_tracked' => 'Stock not tracked',
    ],
    'reconciliation_queries' => [
        'external_source_source' => 'External source: :source',
        'internal_reconciliation_vaniinventoryverify' => 'Internal reconciliation (vani:inventory:verify)',
    ],
    'stock_dashboard_tasks' => [
        'low_stock' => 'Low stock',
        'most' => 'At most',
        'out_stock' => 'Out of stock',
        'sellable_products' => ' sellable products',
        'variants_sale_updated_every_5' => 'Variants on sale · updated every 5 minutes',
    ],
    'stock_unavailable' => [
        'not_enough_stock_variant_variant' => 'Not enough stock for variant #:variant_id: requested :requested, available :available.',
    ],
];
