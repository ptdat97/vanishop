<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'client_provisioning' => [
        'active_keys_revoke_old_key' => ' key còn hiệu lực; thu hồi key cũ trước.',
        'client_already_has' => 'Client đã có ',
        'client_client_code_not_found' => 'Không có client :client_code.',
        'client_code_may_only_contain' => 'Mã client chỉ gồm a-z, 0-9, "-", "_" và không được là "vanishop".',
        'invalid_scope' => 'Scope không hợp lệ: ',
        'invalid_webhook_url_https_required' => 'URL webhook không hợp lệ (production bắt buộc HTTPS).',
        'key_key_id_not_found' => 'Không có key :key_id.',
    ],
    'integration_access_denied' => [
        'client_missing_scope_scope' => 'Client thiếu scope :scope.',
        'integration_api_signature_missing_invalid' => 'Thiếu hoặc sai chữ ký Integration API.',
        'ip_address_not_allowed' => 'Địa chỉ IP không được phép.',
    ],
    'integration_request_rejected' => [
        'idempotency_key_header_required_8' => 'Header Idempotency-Key bắt buộc (8–128 ký tự A-Z a-z 0-9 _ . : -).',
        'order_not_found' => 'Không tìm thấy đơn.',
        'order_was_already_confirmed_different' => 'Đơn đã được xác nhận với số chứng từ khác.',
    ],
    'integration_service_provider' => [
        'integrations' => 'Tích hợp',
        'manage_integration_clients_webhooks' => 'Quản lý Integration Client và webhook',
        'resend_failed_integration_messages' => 'Gửi lại message tích hợp lỗi',
        'view_integration_status_outbox_inbox' => 'Xem tình trạng tích hợp (outbox/inbox, client)',
    ],
    'order_feed' => [
        'invalid_cursor' => 'Cursor không hợp lệ.',
    ],
];
