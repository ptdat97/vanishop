<?php

// Chuỗi giao diện Vue của Admin (ADR-049) — đọc bằng t('<namespace>::admin.<trang>.<khoá>').

return [
    'health_index' => [
        'active_keys_active' => ':active_keys active',
        'attempts' => 'Attempts',
        'create_clients_issue_keys_register' => 'Create clients, issue keys and register webhooks with the commands',
        'enabled_connectors_connectors' => 'Enabled connectors: :connectors',
        'every_failed_message_filter' => 'every failed message in the filter',
        'failed_exhausted' => 'Failed + exhausted',
        'failing_since_failing_since' => '· failing since :failing_since',
        'inbox_failed_exhausted' => 'Inbox failed / exhausted',
        'incoming' => 'Incoming',
        'integrations' => 'Integrations',
        'last_error' => 'Last error',
        'messages_keep_their_id_so' => 'Messages keep their ID, so the receiver can still deduplicate them.',
        'no_clients_yet' => 'No clients yet.',
        'no_messages_match_filter' => 'No messages match the filter.',
        'minutes' => ':count min',
        'oldest' => 'Oldest: :age',
        'outbox_failed_exhausted' => 'Outbox failed / exhausted',
        'outbox_inbox_failed_messages_resending' => 'Outbox/Inbox, failed messages and resending. Payloads have personal data masked.',
        'outbox_pending' => 'Outbox pending',
        'outgoing' => 'Outgoing',
        'paused' => '· paused',
        'reference' => 'Reference',
        'resend' => 'Resend',
        'resend_everything_filter' => 'Resend everything in the filter',
        'resend_label' => 'Resend :label?',
        'resume' => 'Resume',
        'status' => 'Status',
        'text' => 'and',
        'type' => 'Type',
    ],
];
