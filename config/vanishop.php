<?php

$relativeToBase = fn (string $path): string => str_starts_with($path, '/') ? $path : base_path($path);

return [
    /*
    | Phiên bản Core — plugin khai báo "requires.vanishop" dựa trên giá trị này (semver).
    */
    'version' => '0.1.0',

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
    | Đường dẫn gốc dành riêng — slug brand không được trùng (ADR-019). Đường dẫn Admin được thêm tự động.
    */
    'reserved_paths' => ['api', 'tai-khoan', 'up', 'build', 'storage', 'sitemap.xml', 'robots.txt', 'favicon.ico'],

    'media' => [
        // Disk lưu ảnh catalog: 'public' cho dev (cần php artisan storage:link), S3-compatible tại VN cho production.
        'disk' => env('VANI_MEDIA_DISK', 'public'),
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
    ],

    'checkout' => [
        // Slice 6: COD. Cổng thanh toán (PaymentGateway) thêm ở slice Payment / plugin.
        'payment_methods' => ['cod'],
        'shipping' => [
            'flat_fee' => (int) env('VANI_SHIPPING_FLAT_FEE', 30000),
            // Miễn phí giao khi tiền hàng (sau giảm giá) >= ngưỡng; null = không miễn phí.
            'free_over' => env('VANI_SHIPPING_FREE_OVER', 500000) === null ? null : (int) env('VANI_SHIPPING_FREE_OVER', 500000),
        ],
    ],

    'tax' => [
        'calculator' => env('VANI_TAX_CALCULATOR', 'vn_vat_inclusive'),
        // VAT gồm trong giá, basis points (1000 = 10%). Kế toán/pháp chế xác nhận mức áp dụng hiện hành.
        'vat_rate_bp' => (int) env('VANI_VAT_RATE_BP', 1000),
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

    'search' => [
        // database (mặc định, không cần hạ tầng) | meilisearch | provider do plugin đăng ký.
        'provider' => env('VANI_SEARCH_PROVIDER', 'database'),
        'meilisearch' => [
            'host' => env('MEILISEARCH_HOST', 'http://127.0.0.1:7700'),
            'key' => env('MEILISEARCH_KEY'),
            'index' => env('MEILISEARCH_INDEX', 'vani_products'),
        ],
    ],

    'hooks' => [
        // Gọi hook chưa khai báo sẽ ném lỗi (local/testing) thay vì bị bỏ qua.
        'strict' => (bool) env('VANI_HOOKS_STRICT', in_array(env('APP_ENV'), ['local', 'testing'], true)),
    ],
];
