<?php

/*
| Nhãn hiển thị trong Admin (StatusBadge, Select) theo mã; mã chưa có nhãn hiển thị nguyên mã.
*/

return [
    'bulk_action' => ['set' => 'Đặt số tồn =', 'adjust' => 'Cộng/trừ tồn', 'zero' => 'Hết hàng (đặt tồn = 0)', 'safety' => 'Đặt tồn an toàn ='],
    'import_status' => ['change' => 'Thay đổi', 'same' => 'Không đổi', 'unknown_sku' => 'Không có SKU', 'unknown_location' => 'Không có kho', 'external' => 'Kho do hệ thống ngoài quản lý', 'duplicate' => 'Trùng dòng trước', 'invalid' => 'Số tồn không hợp lệ'],
    'default_location' => 'Kho chính',
    'location_type' => ['warehouse' => 'Kho', 'store' => 'Cửa hàng', 'virtual' => 'Ảo (dropship, kho đối tác)'],
    'location_status' => ['active' => 'Đang dùng', 'inactive' => 'Tắt'],
    'stock_action' => ['adjust' => 'Cộng/trừ tồn', 'count' => 'Kiểm kê (đặt số đếm)', 'safety' => 'Tồn an toàn'],
    'transfer_status' => ['pending' => 'Chờ gửi', 'shipped' => 'Đang đi đường', 'received' => 'Đã nhận', 'cancelled' => 'Đã huỷ'],
    'discrepancy' => ['external_mismatch' => 'Nguồn ngoài ≠ VaniShop', 'reserved_mismatch' => 'Hàng giữ ≠ reserved', 'reserved_off_ledger' => 'reserved lệch sổ biến động', 'on_hand_off_ledger' => 'Tồn lệch sổ biến động', 'negative_on_hand' => 'Tồn âm'],
    'resolution' => ['repaired' => 'Đã sửa reserved', 'applied' => 'Đã áp số nguồn', 'rejected' => 'Bị từ chối — xem ghi chú'],
];
