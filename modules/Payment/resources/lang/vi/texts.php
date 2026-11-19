<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'order_payment_panel' => [
        'awaiting_payout' => ' (chờ chuyển trả ',
        'capture' => 'Thu tiền',
        'cash_collected' => 'Đã thu tiền ',
        'cod_collected' => 'Đã thu COD',
        'confirm_money_received' => 'Xác nhận đã nhận tiền',
        'confirm_payment' => 'Xác nhận thanh toán',
        'gateway_reported_result' => 'Cổng báo kết quả',
        'no_payments_yet' => 'Chưa có thanh toán',
        'note_bank_transaction_id_checked' => 'Ghi chú (mã giao dịch ngân hàng, người kiểm tra…)',
        'note_collected_store_delivered_itself' => 'Ghi chú (người thu, shop tự giao, hãng chưa báo giao…)',
        'note_refund_transfer_transaction_id' => 'Ghi chú (mã giao dịch chuyển trả)',
        'payment' => 'Thanh toán',
        'payment_2' => 'Thanh toán: ',
        'payment_management' => 'Quản lý thanh toán',
        'reason' => 'Lý do',
        'refund' => 'Hoàn tiền',
        'refund_amount_most' => 'Số tiền hoàn (tối đa ',
        'refund_paid' => 'Đã chuyển trả ',
        'refunded' => 'Đã hoàn',
    ],
    'payment_dashboard_tasks' => [
        'reconciliation_mismatches' => 'Đối soát lệch',
        'refund_awaiting_payout' => 'Hoàn tiền chờ chuyển trả',
        'statement_does_not_match_order' => 'Sao kê/bảng kê không khớp đơn',
    ],
    'payment_service_provider' => [
        'confirm_money_received_manual_bank' => 'Xác nhận đã nhận tiền (chuyển khoản thủ công)',
        'no_cash_delivery_gateway_collectsondelivery' => 'Không có cổng thu tiền khi giao (collectsOnDelivery) đang nhận giao dịch mới — đổi hàng phải bù chênh sẽ bị từ chối. Bật vani.cod hoặc cổng tương tự.',
        'open_payments_still_pending_authorised' => 'còn :open khoản thanh toán chờ/giữ tiền qua cổng :code',
        'payment' => 'Thanh toán',
        'payment_gateways' => 'Cổng thanh toán',
        'refund' => 'Hoàn tiền',
        'view_payments' => 'Xem thanh toán',
    ],
    'payment_settlement' => [
        'authorised_amount_does_not_match' => 'Số tiền giữ không khớp khoản thanh toán',
        'captured_amount_does_not_match' => 'Số tiền thu không khớp khoản thanh toán',
    ],
    'refund_service' => [
        'pay_out_via_label' => 'Chuyển trả qua :label',
    ],
    'settings' => [
        'capture_on' => 'Thu tiền thanh toán online đã giữ',
        'capture_on_help' => 'Cổng thanh toán giữ tiền trước (thẻ): chọn lúc trừ tiền thật của khách.',
        'capture_on_manual' => 'Khi nhân viên bấm "Thu tiền"',
        'capture_on_shipped' => 'Khi vận đơn rời kho',
    ],
];
