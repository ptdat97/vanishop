<?php

/*
| Hook công khai của module Notification. Danh mục tổng: docs/04-extension/extension-point-catalog.md
*/

return [
    'vani.service.notification.request' => [
        'type' => 'filter',
        'visibility' => 'public',
        'since' => '0.3.47',
        'args' => ['request' => 'Modules\\Notification\\Contracts\\Data\\NotificationRequest'],
        'description' => 'Yêu cầu gửi thông báo trước khi chọn kênh và mẫu tin (thêm biến cho mẫu, đổi người nhận). Có thể chạy trong transaction nghiệp vụ: không I/O mạng.',
    ],
];
