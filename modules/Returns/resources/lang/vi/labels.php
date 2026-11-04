<?php

/*
| Nhãn hiển thị trong Admin (StatusBadge, Select) theo mã; mã chưa có nhãn hiển thị nguyên mã.
*/

return [
    'return_status' => ['requested' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'in_transit' => 'Đang gửi về', 'received' => 'Đã nhận hàng', 'resolved' => 'Hoàn tất', 'rejected' => 'Từ chối', 'cancelled' => 'Đã huỷ'],
    'reason' => ['wrong_size' => 'Sai size', 'not_as_described' => 'Không đúng mô tả', 'defective' => 'Lỗi sản phẩm', 'changed_mind' => 'Đổi ý', 'other' => 'Khác'],
    'condition' => ['sellable' => 'Bán lại được (nhập kho)', 'damaged' => 'Hư hỏng (không nhập kho)'],
];
