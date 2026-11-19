<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'wishlist_service_provider' => [
        'compared_price_when_added_last' => 'So với giá lúc thêm (hoặc lần báo trước). Tối đa một tin mỗi sản phẩm trong 7 ngày.',
        'hi_customer_name_product_name' => 'Chào {{ customer_name }},

{{ product_name }} trong danh sách yêu thích của bạn đã có hàng lại: {{ product_url }}',
        'hi_customer_name_product_name_2' => 'Chào {{ customer_name }},

{{ product_name }} trong danh sách yêu thích của bạn giảm còn {{ price }}: {{ product_url }}',
        'marketing_message_only_sent_customers' => 'Tin marketing: chỉ gửi cho khách đồng ý nhận tin. Mẫu tin ở Admin → Thông báo.',
        'notify_customers_when_wishlisted_product' => 'Báo khách khi sản phẩm yêu thích có hàng lại',
        'notify_customers_when_wishlisted_product_2' => 'Báo khách khi sản phẩm yêu thích giảm giá',
        'product_name_back_stock' => '{{ product_name }} đã có hàng lại',
        'product_name_sale' => '{{ product_name }} đang giảm giá',
        'wishlisted_product_back_stock' => 'Sản phẩm yêu thích có hàng lại',
        'wishlisted_product_sale' => 'Sản phẩm yêu thích giảm giá',
    ],
];
