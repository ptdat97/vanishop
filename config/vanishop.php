<?php

$relativeToBase = fn (string $path): string => str_starts_with($path, '/') ? $path : base_path($path);

return [
    /*
    | Phiên bản Core — plugin khai báo "requires.vanishop" dựa trên giá trị này (semver).
    */
    'version' => '0.3.12',

    'plugins' => [
        'path' => $relativeToBase(env('VANI_PLUGINS_PATH', 'custom/plugin')),
        'cache' => $relativeToBase(env('VANI_PLUGINS_CACHE', 'bootstrap/cache/vanishop-plugins.php')),
        // Tắt toàn bộ plugin khi có sự cố (xem docs/05-plugin/plugin-system.md §8).
        'safe_mode' => (bool) env('VANI_PLUGINS_SAFE_MODE', false),
    ],

    /*
    | Admin trên domain chung (ADR-020): đường dẫn cấu hình được, không dùng 2FA nên cần kiểm soát bù trừ.
    */
    'admin' => [
        // Production: đặt giá trị khó đoán, ví dụ "quan-tri-7f3k".
        'path' => env('VANI_ADMIN_PATH', 'admin'),
        // IP/CIDR được phép vào Admin, phân tách bằng dấu phẩy. Rỗng = không giới hạn.
        'ip_allowlist' => array_values(array_filter(array_map('trim', explode(',', (string) env('VANI_ADMIN_IP_ALLOWLIST', ''))))),
        // Cookie phiên riêng của Admin (tách khỏi phiên khách hàng trên storefront).
        'session_cookie' => env('VANI_ADMIN_SESSION_COOKIE', 'vanishop_admin_session'),
        // Hết phiên sau N phút không hoạt động.
        'idle_minutes' => (int) env('VANI_ADMIN_IDLE_MINUTES', 30),
        // Kiểm tra mật khẩu có trong danh sách bị lộ (gọi dịch vụ ngoài, chỉ gửi 5 ký tự đầu của hash SHA-1).
        'check_breached_passwords' => (bool) env('VANI_ADMIN_CHECK_BREACHED_PASSWORDS', env('APP_ENV') === 'production'),
    ],

    /*
    | Đường dẫn gốc dành riêng của storefront — VANI_ADMIN_PATH không được trùng.
    */
    'reserved_paths' => [
        'api', 'tai-khoan', 'up', 'build', 'storage', 'sitemap.xml', 'robots.txt', 'favicon.ico',
        'danh-muc', 'thuong-hieu', 'tim-kiem', 'san-pham', 'gio-hang', 'thanh-toan', 'don-hang', 'p', 'tra-cuu-don',
    ],

    /*
    | Native storefront (ADR-025): thư mục theme và theme mặc định (Admin → Cấu hình `core.theme` ghi đè).
    */
    'storefront' => [
        'themes_path' => $relativeToBase(env('VANI_THEMES_PATH', 'custom/theme')),
        'theme' => env('VANI_THEME', 'vani-base'),
    ],

    'media' => [
        // Disk lưu ảnh catalog: 'public' cho dev (cần php artisan storage:link), S3-compatible tại VN cho production.
        'disk' => env('VANI_MEDIA_DISK', 'public'),
    ],

    /*
    | Ngôn ngữ storefront: mặc định + danh sách được hỗ trợ (đổi bằng header X-Vani-Locale).
    */
    'locale' => [
        'default' => env('VANI_LOCALE', 'vi'),
        'supported' => ['vi', 'en'],
    ],

    /*
    | Tiền tệ của cửa hàng (một cửa hàng — một tiền tệ, ADR-028).
    */
    'currency' => env('VANI_CURRENCY', 'VND'),

    /*
    | Đơn hàng: số đơn một dãy cho cả cửa hàng (ADR-028), dạng <tiền tố><yymm>-<6 số>.
    */
    'orders' => [
        'number_prefix' => env('VANI_ORDER_NUMBER_PREFIX', 'VN'),
    ],

    'pricing' => [
        // Chiến lược chọn giá (extension point PricingStrategy).
        'strategy' => env('VANI_PRICING_STRATEGY', 'price_list_priority'),
    ],

    'cart' => [
        // Giới hạn chống giỏ ảo / gom hàng.
        'max_line_quantity' => (int) env('VANI_CART_MAX_LINE_QUANTITY', 20),
        'max_lines' => (int) env('VANI_CART_MAX_LINES', 50),
        // Giỏ không hoạt động quá số ngày này bị xoá (vani:cart:prune, chạy hằng ngày).
        'ttl_days' => (int) env('VANI_CART_TTL_DAYS', 30),
        // Giỏ của khách không hoạt động quá số phút này → CartAbandoned (vani:cart:detect-abandoned, 5 phút/lần).
        'abandoned_after_minutes' => (int) env('VANI_CART_ABANDONED_AFTER_MINUTES', 60),
    ],

    /*
    | COD, chuyển khoản, phí giao cố định, VAT VN là plugin hệ thống (ADR-029) — cấu hình nằm trong
    | custom/plugin/{Cod,BankTransfer,ShippingFlatRate,TaxVnVat}/Config và Admin → Cấu hình.
    */

    'payment' => [
        // Cổng giữ tiền (CapturesLater): `shipped` = thu khi vận đơn rời kho; `manual` = nhân viên bấm thu.
        'capture_on' => env('VANI_PAYMENT_CAPTURE_ON', 'shipped'),
    ],

    'fulfillment' => [
        // Tự tạo vận đơn khi đơn được xác nhận.
        'auto_create' => (bool) env('VANI_FULFILLMENT_AUTO_CREATE', true),
        // Carrier mặc định cho vận đơn mới (plugin hãng: ghn, ghtk…).
        'default_carrier' => env('VANI_FULFILLMENT_CARRIER', 'manual'),
        'sourcing' => env('VANI_FULFILLMENT_SOURCING', 'reserved_locations'),
        // Số ngày sau khi giao để đơn chuyển "completed" (hết hạn đổi trả).
        'return_window_days' => (int) env('VANI_RETURN_WINDOW_DAYS', 7),
    ],

    'returns' => [
        // ReturnPolicy (extension point). days_window dùng VANI_RETURN_WINDOW_DAYS.
        'policy' => env('VANI_RETURN_POLICY', 'days_window'),
        'reasons' => ['wrong_size', 'not_as_described', 'defective', 'changed_mind', 'other'],
    ],

    'tax' => [
        // Mã TaxCalculator mặc định (plugin vani.tax-vn-vat: `vn_vat_inclusive`); không có hiệu lực → `none`.
        'calculator' => env('VANI_TAX_CALCULATOR', 'vn_vat_inclusive'),
    ],

    'promotion' => [
        // Giá sàn: tổng giảm của một dòng không vượt tỷ lệ này (basis points; 5000 = 50%, NĐ 81/2018 — pháp chế xác nhận).
        'max_discount_bp' => (int) env('VANI_PROMOTION_MAX_DISCOUNT_BP', 5000),
    ],

    'inventory' => [
        // Extension point InventoryStrategy (ví dụ channel allocation). Chỉ được giảm ATS.
        'strategy' => env('VANI_INVENTORY_STRATEGY', 'standard'),
        // Storefront hiển thị "sắp hết hàng" khi ATS <= ngưỡng này (không lộ số tồn chính xác).
        'low_stock_threshold' => (int) env('VANI_LOW_STOCK_THRESHOLD', 3),
        // Thời gian giữ hàng cho đơn thanh toán online (giây).
        'reservation_ttl' => (int) env('VANI_RESERVATION_TTL', 900),
    ],

    'customer' => [
        // Token Bearer của Storefront API (ADR-024).
        'token_ttl_days' => (int) env('VANI_CUSTOMER_TOKEN_TTL_DAYS', 90),
        // Đăng nhập mạng xã hội (AuthProvider): redirect_uri phải bắt đầu bằng một trong các tiền tố này (chống open redirect).
        'auth_redirect_uris' => array_values(array_filter(array_map('trim', explode(',', (string) env('VANI_AUTH_REDIRECT_URIS', (string) env('APP_URL', '')))))),
        'otp' => [
            // Giới hạn yêu cầu OTP trong cửa sổ `window` giây (chống SMS pumping).
            'per_phone' => (int) env('VANI_OTP_PER_PHONE', 3),
            'per_ip' => (int) env('VANI_OTP_PER_IP', 10),
            'window' => (int) env('VANI_OTP_WINDOW', 600),
            // CHỈ dev: ghi mã OTP vào log khi chưa có kênh SMS/ZNS.
            'log_sender' => (bool) env('VANI_OTP_LOG_SENDER', env('APP_ENV') === 'local'),
        ],
    ],

    'integration' => [
        // Backoff giữa các lần gửi lại (giây); hết danh sách → dead. docs/11-integration/integration-platform.md §7
        'retry_delays' => [60, 300, 900, 3600, 21600, 86400],
        'retry_jitter' => 0.2,
        // Message "processing" quá lâu (worker chết) được trả về hàng đợi.
        'processing_timeout' => (int) env('VANI_INTEGRATION_PROCESSING_TIMEOUT', 600),
        // Webhook lỗi liên tục quá số giờ này → subscription tạm dừng.
        'webhook_pause_after_hours' => (int) env('VANI_WEBHOOK_PAUSE_AFTER_HOURS', 24),
        // Circuit breaker theo connector: mở sau N lỗi retryable liên tiếp, thử lại sau M giây.
        'circuit_threshold' => (int) env('VANI_INTEGRATION_CIRCUIT_THRESHOLD', 5),
        'circuit_cooldown' => (int) env('VANI_INTEGRATION_CIRCUIT_COOLDOWN', 60),
    ],

    'search' => [
        // database (mặc định, không cần hạ tầng) | mã provider do plugin đăng ký (vd. meilisearch — plugin
        // vani.search-meilisearch, cấu hình MEILISEARCH_* nằm trong plugin). Provider chưa bật → dùng database.
        'provider' => env('VANI_SEARCH_PROVIDER', 'database'),
    ],

    'hooks' => [
        // Gọi hook chưa khai báo sẽ ném lỗi (local/testing) thay vì bị bỏ qua.
        'strict' => (bool) env('VANI_HOOKS_STRICT', in_array(env('APP_ENV'), ['local', 'testing'], true)),
        // Listener chạy lâu hơn ngưỡng này (ms) được ghi cảnh báo `hook_duration_ms` kèm plugin.
        'slow_ms' => (float) env('VANI_HOOKS_SLOW_MS', 50),
    ],
];
