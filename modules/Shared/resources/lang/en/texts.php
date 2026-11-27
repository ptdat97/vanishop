<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'alert' => [
        'normal' => 'NORMAL',
        'ongoing_level' => '[ONGOING] [:level]',
        'resolved_level' => '[RESOLVED] [:level]',
        'urgent' => 'URGENT',
    ],
    'app_service_provider' => [
        'phone_number_format' => 'Phone number format',
        'view_operations_dashboard_queues_performance' => 'View the operations dashboard (queues, performance)',
    ],
    'external_calls' => [
        'external_service_call_call_inside' => 'External service call [:call] inside an uncommitted DB transaction — move the call outside the transaction or use DB::afterCommit().',
    ],
    'idempotency_conflict' => [
        'idempotency_key_was_already_used' => 'The Idempotency-Key was already used for another request.',
        'request_idempotency_key_being_processed' => 'A request with this Idempotency-Key is being processed. Please try again later.',
    ],
    'weekly_schedule_invalid' => 'Invalid weekly schedule: pick at least one day, times as HH:MM, at most 3 windows.',
];
