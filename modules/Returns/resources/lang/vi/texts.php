<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'order_returns_panel' => [
        'approve_return_exchange' => 'Duyệt đổi/trả',
        'complete_create_exchange_order' => 'Hoàn tất & tạo đơn đổi',
        'complete_exchange_create_exchange_order' => 'Hoàn tất đổi hàng và tạo đơn đổi?',
        'complete_refund' => 'Hoàn tất & hoàn tiền',
        'create_return_exchange_request' => 'Tạo yêu cầu đổi/trả',
        'customer_shipped_goods' => 'Khách đã gửi hàng',
        'exchange' => ' (đổi hàng)',
        'minus_damage_fees_if_any' => '; trừ phí hư hỏng nếu có)',
        'note' => 'Ghi chú',
        'open_return_exchange_request' => 'Mở yêu cầu đổi/trả',
        'receive_return_restock_every_line' => 'Nhận hàng trả, nhập lại kho tất cả dòng? (Có hàng hỏng: khai ở trang yêu cầu.)',
        'received_still_sellable' => 'Đã nhận hàng (còn bán được)',
        'record_customer_shipped_goods_back' => 'Ghi nhận khách đã gửi hàng về?',
        'refund_amount_most' => 'Số tiền hoàn (tối đa ',
        'reject' => 'Từ chối',
        'rejection_reason_sent_customer' => 'Lý do từ chối (gửi cho khách)',
        'return_exchange' => 'Đổi/trả',
    ],
    'return_controller' => [
        'approve' => 'Duyệt',
        'received_damaged_not_restocked' => 'Đã nhận hàng (hỏng, không nhập kho)',
        'received_still_sellable' => 'Đã nhận hàng (còn bán được)',
    ],
    'return_dashboard_tasks' => [
        'returns_exchanges_awaiting_approval' => 'Đổi/trả chờ duyệt',
        'returns_received_awaiting_completion' => 'Hàng trả đã nhận, chờ hoàn tất',
    ],
    'returns_service_provider' => [
        'approve_reject_receive_returns' => 'Duyệt, từ chối, nhận hàng trả',
        'complete_returns_exchanges_refund' => 'Hoàn tất đổi/trả và hoàn tiền',
        'return_exchange' => 'Đổi/trả',
        'return_policy' => 'Chính sách đổi trả',
        'returnpolicy_applied_store_s_orders' => 'ReturnPolicy áp cho đơn của cửa hàng.',
        'view_return_exchange_requests' => 'Xem yêu cầu đổi/trả',
    ],
    'settings' => [
        'attachments_retention_days' => 'Giữ ảnh đổi trả (ngày)',
        'attachments_retention_days_help' => 'Ảnh khách gửi kèm yêu cầu đổi trả bị xoá sau số ngày này kể từ khi yêu cầu đóng.',
    ],
];
