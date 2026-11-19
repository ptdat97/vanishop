<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'tracking_pixels_service_provider' => [
        'consent_bar_text_locale' => 'Consent bar text (:locale)',
        'consent_js_expression' => 'Consent JS expression',
        'cookie_consent' => 'Cookie consent',
        'debug_mode' => 'Debug mode',
        'do_not_ask_always_run' => 'Do not ask (always run)',
        'example_123456789012345_empty_meta_off' => 'For example 123456789012345. Empty = Meta off.',
        'example_gtm_xxxxxxx_empty_gtm' => 'For example GTM-XXXXXXX. Empty = GTM off.',
        'example_xxxxxxxxxx_empty_ga4_off' => 'For example G-XXXXXXXXXX. Empty = GA4 off.',
        'js_expression_another_consent_tool' => 'JS expression of another consent tool',
        'logs_send_errors_uses_ga4' => 'Logs send errors, uses GA4\'s debug endpoint and prints events to the browser console.',
        'meta_test_event_code' => 'Meta Test Event code',
        'only_when_testing_events_manager' => 'Only when testing in Events Manager.',
        'send_purchase_events_from_server' => 'Send purchase events from the server to Meta (Conversions API)',
        'send_purchase_events_from_server_2' => 'Send purchase events from the server to GA4 (Measurement Protocol)',
        'show_plugin_s_consent_bar' => 'Show the plugin\'s consent bar',
        'we_use_cookies_measure_advertising' => 'We use cookies to measure advertising and improve your shopping experience.',
        'when_js_expression_chosen_example' => 'When "JS expression" is chosen. For example: window.CookieConsent && window.CookieConsent.acceptedCategory("analytics")',
        'without_consent_ad_scripts_not' => 'Without consent, ad scripts are not loaded and server events are not sent.',
    ],
];
