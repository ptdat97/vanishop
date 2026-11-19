<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'tracking_pixels_service_provider' => [
        'consent_bar_text_locale' => 'Nội dung thanh đồng ý (:locale)',
        'consent_js_expression' => 'Biểu thức JS đồng ý',
        'cookie_consent' => 'Đồng ý cookie',
        'debug_mode' => 'Chế độ debug',
        'do_not_ask_always_run' => 'Không hỏi (luôn chạy)',
        'example_123456789012345_empty_meta_off' => 'Ví dụ 123456789012345. Trống = tắt Meta.',
        'example_gtm_xxxxxxx_empty_gtm' => 'Ví dụ GTM-XXXXXXX. Trống = tắt GTM.',
        'example_xxxxxxxxxx_empty_ga4_off' => 'Ví dụ G-XXXXXXXXXX. Trống = tắt GA4.',
        'js_expression_another_consent_tool' => 'Biểu thức JS của công cụ đồng ý khác',
        'logs_send_errors_uses_ga4' => 'Ghi log lỗi gửi, dùng endpoint debug của GA4, in sự kiện ra console trình duyệt.',
        'meta_test_event_code' => 'Mã Test Event của Meta',
        'only_when_testing_events_manager' => 'Chỉ dùng khi thử trong Events Manager.',
        'send_purchase_events_from_server' => 'Gửi sự kiện mua từ server tới Meta (Conversions API)',
        'send_purchase_events_from_server_2' => 'Gửi sự kiện mua từ server tới GA4 (Measurement Protocol)',
        'show_plugin_s_consent_bar' => 'Hiện thanh đồng ý của plugin',
        'we_use_cookies_measure_advertising' => 'Chúng tôi dùng cookie để đo hiệu quả quảng cáo và cải thiện trải nghiệm mua sắm.',
        'when_js_expression_chosen_example' => 'Khi chọn "Biểu thức JS". Ví dụ: window.CookieConsent && window.CookieConsent.acceptedCategory("analytics")',
        'without_consent_ad_scripts_not' => 'Chưa đồng ý thì không nạp script quảng cáo và không gửi sự kiện từ server.',
    ],
];
