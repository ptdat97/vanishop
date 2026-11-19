<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'client_provisioning' => [
        'active_keys_revoke_old_key' => ' active keys; revoke an old key first.',
        'client_already_has' => 'The client already has',
        'client_client_code_not_found' => 'Client :client_code not found.',
        'client_code_may_only_contain' => 'The client code may only contain a-z, 0-9, "-", "_" and must not be "vanishop".',
        'invalid_scope' => 'Invalid scope:',
        'invalid_webhook_url_https_required' => 'Invalid webhook URL (HTTPS is required in production).',
        'key_key_id_not_found' => 'Key :key_id not found.',
    ],
    'integration_access_denied' => [
        'client_missing_scope_scope' => 'The client is missing the scope :scope.',
        'integration_api_signature_missing_invalid' => 'Integration API signature missing or invalid.',
        'ip_address_not_allowed' => 'The IP address is not allowed.',
    ],
    'integration_request_rejected' => [
        'idempotency_key_header_required_8' => 'The Idempotency-Key header is required (8–128 characters A-Z a-z 0-9 _ . : -).',
        'order_not_found' => 'Order not found.',
        'order_was_already_confirmed_different' => 'The order was already confirmed with a different document number.',
    ],
    'integration_service_provider' => [
        'integrations' => 'Integrations',
        'manage_integration_clients_webhooks' => 'Manage Integration Clients and webhooks',
        'resend_failed_integration_messages' => 'Resend failed integration messages',
        'view_integration_status_outbox_inbox' => 'View the integration status (outbox/inbox, clients)',
    ],
    'order_feed' => [
        'invalid_cursor' => 'Invalid cursor.',
    ],
];
