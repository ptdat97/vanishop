<?php

return [
    'external_authority' => 'Tồn vật lý của location này do hệ thống ngoài [:authority] quản lý; không điều chỉnh tay ở VaniShop.',
    'stale' => 'Dữ liệu đã được người khác cập nhật. Vui lòng tải lại trang.',
    'saved' => 'Đã lưu.',
    'adjusted' => 'Đã cập nhật tồn.',

    'transfer_created' => 'Đã tạo phiếu chuyển kho.',
    'transfer_shipped' => 'Đã gửi hàng; tồn kho đi đã được trừ.',
    'transfer_received' => 'Đã nhận hàng; tồn kho đến đã được cộng.',
    'transfer_cancelled' => 'Đã huỷ phiếu chuyển kho.',
    'transfer_no_lines' => 'Phiếu chuyển kho phải có ít nhất một dòng.',
    'transfer_same_location' => 'Kho đi và kho đến phải khác nhau.',
    'transfer_external_authority' => 'Chỉ chuyển kho giữa các location do VaniShop quản lý tồn vật lý.',
    'transfer_invalid_transition' => 'Không thể chuyển phiếu từ [:from] sang [:to].',
    'transfer_received_range' => 'Số nhận của variant #:variant phải trong khoảng 0–:max.',
    'transfer_unknown_sku' => 'Không tìm thấy SKU [:sku].',

    'reconcile_source_required' => '--source là bắt buộc và phải khác "vanishop".',
    'reconcile_input_required' => 'Cần --file hoặc --json cho snapshot.',
    'reconcile_invalid_json' => 'JSON snapshot không hợp lệ.',
];
