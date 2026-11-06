<?php

return [
    'bulk_confirmed' => 'Đã xác nhận :count đơn; bỏ qua :skipped đơn không còn chờ xác nhận.',
    'transition_invalid' => 'Không thể chuyển đơn từ trạng thái :from sang :to.',
    'cannot_cancel' => 'Đơn đã được xử lý hoặc giao, không thể huỷ.',
    'cannot_change_address' => 'Đơn đã bắt đầu giao hoặc đã đóng, không thể đổi địa chỉ.',
    'stale' => 'Đơn đã được người khác cập nhật. Vui lòng tải lại trang.',
    'not_found' => 'Không tìm thấy đơn hàng.',
    'confirmed' => 'Đã xác nhận đơn.',
    'cancelled' => 'Đã huỷ đơn.',
    'cannot_cancel_lines' => 'Chỉ huỷ một phần được khi đơn đã xác nhận, hàng chưa rời kho và thanh toán là COD chờ thu hoặc đã thu.',
    'invalid_cancel_quantities' => 'Số lượng huỷ không hợp lệ (vượt số còn lại, dòng không thuộc đơn, hoặc huỷ hết — hãy huỷ cả đơn).',
    'lines_cancelled' => 'Đã huỷ một phần đơn.',
    'address_invalid' => 'Tỉnh/thành hoặc phường/xã không hợp lệ theo danh mục hành chính hiện hành.',
    'address_changed' => 'Đã đổi địa chỉ giao hàng.',
    'note_added' => 'Đã thêm ghi chú.',
    'deletion_open_order' => 'đơn :number chưa hoàn tất',
    'deletion_open_return' => 'đổi trả của đơn :number đang xử lý',
    'cannot_confirm_receipt' => 'Chỉ xác nhận đã nhận hàng khi đơn đã giao (hoặc đang giao đã lâu) và chưa xác nhận.',
];
