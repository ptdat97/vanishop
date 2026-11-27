<?php

return [
    'action_done' => 'Đã thực hiện ":action".',
    'action_failed' => 'Không thực hiện được ":action". Vui lòng thử lại hoặc liên hệ quản trị.',
    'bulk_done' => '":action": :done thành công, :failed lỗi.',
    'cache_cleared' => 'Đã xoá cache. Trang và dữ liệu được dựng lại ở lượt xem kế tiếp.',
    'cache_shares_redis_database' => 'Không xoá được: store cache dùng chung database Redis với phiên đăng nhập/queue. Đặt REDIS_CACHE_DB riêng (docs/18-operations/production.md).',
    'cache_shares_durable_store' => 'Không xoá được: store cache mặc định trùng store durable (CACHE_DURABLE_STORE) — xoá sẽ mất token và bản xem trước nhập file.',
];
