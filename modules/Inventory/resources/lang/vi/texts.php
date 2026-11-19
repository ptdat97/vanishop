<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'inventory_service_provider' => [
        'adjust_count_inventory' => 'Điều chỉnh/kiểm kê tồn kho',
        'below_sellable_quantity_admin_highlights' => 'Số bán được từ mức này trở xuống: Admin tô cảnh báo, Tổng quan đếm vào "Sắp hết hàng", storefront báo "sắp hết hàng". 0 = chỉ báo khi hết.',
        'edit_main_warehouse_details_create' => 'Sửa thông tin Kho chính, tạo Kho chính (cấp Owner)',
        'inventory' => 'Tồn kho',
        'low_stock_threshold' => 'Ngưỡng "sắp hết hàng"',
        'sellable_quantity_calculation_ats' => 'Cách tính số bán được (ATS)',
        'sellable_stock_strategy' => 'Chiến lược tồn bán được',
        'strategy_only_reduce_ats_compared' => 'Strategy chỉ giảm được ATS so với công thức chuẩn.',
        'view_inventory' => 'Xem tồn kho',
    ],
    'not_stock_authority' => [
        'not_stock_authority_location_location' => 'Không phải authority tồn kho của location :location_code.',
    ],
    'product_list_stock' => [
        'out_stock' => 'Hết hàng',
        'sellable_low_stock' => ':sellable · sắp hết',
        'sellable_stock' => 'Tồn bán được',
        'stock_not_tracked' => 'Không theo dõi tồn',
    ],
    'reconciliation_queries' => [
        'external_source_source' => 'Nguồn ngoài: :source',
        'internal_reconciliation_vaniinventoryverify' => 'Đối soát nội bộ (vani:inventory:verify)',
    ],
    'stock_dashboard_tasks' => [
        'low_stock' => 'Sắp hết hàng',
        'most' => 'Còn ≤ ',
        'out_stock' => 'Hết hàng',
        'sellable_products' => ' sản phẩm bán được',
        'variants_sale_updated_every_5' => 'Biến thể đang bán · cập nhật 5 phút/lần',
    ],
    'stock_unavailable' => [
        'not_enough_stock_variant_variant' => 'Không đủ hàng cho variant #:variant_id: cần :requested, còn :available.',
    ],
];
