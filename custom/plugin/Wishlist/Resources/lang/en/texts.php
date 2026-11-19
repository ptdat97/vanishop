<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'wishlist_service_provider' => [
        'compared_price_when_added_last' => 'Compared with the price when added (or the last notice). At most one message per product every 7 days.',
        'hi_customer_name_product_name' => 'Hi {{ customer_name }},

{{ product_name }} from your wishlist is back in stock: {{ product_url }}',
        'hi_customer_name_product_name_2' => 'Hi {{ customer_name }},

{{ product_name }} from your wishlist is now {{ price }}: {{ product_url }}',
        'marketing_message_only_sent_customers' => 'Marketing message: only sent to customers who opted in. Templates under Admin → Notifications.',
        'notify_customers_when_wishlisted_product' => 'Notify customers when a wishlisted product is back in stock',
        'notify_customers_when_wishlisted_product_2' => 'Notify customers when a wishlisted product goes on sale',
        'product_name_back_stock' => '{{ product_name }} is back in stock',
        'product_name_sale' => '{{ product_name }} is on sale',
        'wishlisted_product_back_stock' => 'Wishlisted product back in stock',
        'wishlisted_product_sale' => 'Wishlisted product on sale',
    ],
];
