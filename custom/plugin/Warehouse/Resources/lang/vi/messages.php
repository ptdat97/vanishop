<?php

// Chuỗi Admin của vani.warehouse (ADR-042).
return [
    'stale' => 'Dữ liệu đã được người khác cập nhật. Vui lòng tải lại trang.',
    'saved' => 'Đã lưu.',
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
    'transfers_in_transit' => 'Còn :count phiếu chuyển kho đang đi đường — nhận hoặc huỷ trước khi tắt Vận hành kho.',
];
