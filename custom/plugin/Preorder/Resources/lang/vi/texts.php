<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'preorder_service_provider' => [
        'arrival_date_sku_invalid_use' => 'Ngày về của :sku không hợp lệ — dùng dd/mm/yyyy.',
        'arriving' => ' · về ',
        'awaiting_stock' => ' · chờ hàng ',
        'awaiting_stock_2' => 'chờ hàng về',
        'csv_xlsx_file_column_1' => 'File .csv/.xlsx: cột 1 SKU, cột 2 số lượng đang về (0 = xoá), cột 3 ngày dự kiến dd/mm/yyyy (tuỳ chọn).',
        'do_not_hand_orders_still' => 'Không cho đẩy lên hãng đơn còn chờ hàng',
        'expected' => 'dự kiến ',
        'file_has_no_matching_sku' => 'File không có SKU nào khớp ở cột đầu.',
        'import_incoming_stock' => 'Nhập hàng đang về',
        'incoming' => 'Đang về',
        'not_found' => ' Không tìm thấy: ',
        'open_pre_orders_what_incoming' => 'Mở bán đặt trước bao nhiêu % hàng đang về (0–100)',
        'pre_order_awaiting_stock' => 'Đặt trước — chờ hàng',
        'pre_orders_past_their_expected' => 'Đặt trước quá ngày dự kiến (SKU còn đơn chờ)',
        'receive_stock_when_arrives_then' => ' — nhập kho khi hàng về rồi đẩy lên hãng, hoặc "Giao trước phần có hàng" trên trang đơn',
        'shipment_has_items_awaiting_stock' => 'Vận đơn còn món chờ hàng: ',
        'text_50_40_units_incoming_20' => 'Vd. 50: đang về 40 cái thì mở 20 suất đặt trước. 0 = tạm dừng đặt trước, vẫn hiện "Đang về" ở Tồn kho.',
        'updated_incoming_stock_saved_skus' => 'Đã cập nhật hàng đang về cho :saved SKU.',
    ],
];
