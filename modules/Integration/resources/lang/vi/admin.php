<?php

// Chuỗi giao diện Vue của Admin (ADR-049) — đọc bằng t('<namespace>::admin.<trang>.<khoá>').

return [
    'health_index' => [
        'active_keys_active' => ':active_keys còn hiệu lực',
        'attempts' => 'Lần thử',
        'create_clients_issue_keys_register' => 'Tạo client, cấp key và đăng ký webhook bằng lệnh',
        'enabled_connectors_connectors' => 'Connector đang bật: :connectors',
        'every_failed_message_filter' => 'mọi message lỗi đang lọc',
        'failed_exhausted' => 'Lỗi + hết lượt',
        'failing_since_failing_since' => '· lỗi từ :failing_since',
        'inbox_failed_exhausted' => 'Inbox lỗi / hết lượt',
        'incoming' => 'Nhận vào',
        'integrations' => 'Tích hợp',
        'last_error' => 'Lỗi gần nhất',
        'messages_keep_their_id_so' => 'Message giữ nguyên ID nên phía nhận vẫn khử trùng lặp được.',
        'no_clients_yet' => 'Chưa có client nào.',
        'no_messages_match_filter' => 'Không có message nào khớp bộ lọc.',
        'minutes' => ':count phút',
        'oldest' => 'Cũ nhất: :age',
        'outbox_failed_exhausted' => 'Outbox lỗi / hết lượt',
        'outbox_inbox_failed_messages_resending' => 'Outbox/Inbox, message lỗi và gửi lại. Payload đã che thông tin cá nhân.',
        'outbox_pending' => 'Outbox chờ gửi',
        'outgoing' => 'Gửi đi',
        'paused' => '· tạm dừng',
        'reference' => 'Tham chiếu',
        'resend' => 'Gửi lại',
        'resend_everything_filter' => 'Gửi lại tất cả đang lọc',
        'resend_label' => 'Gửi lại :label?',
        'resume' => 'Bật lại',
        'status' => 'Trạng thái',
        'text' => 'và',
        'type' => 'Loại',
    ],
];
