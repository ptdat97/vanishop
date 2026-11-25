<?php

return [
    'type_label' => 'Nhắc giỏ bỏ quên',
    'customer_fallback' => 'bạn',
    'default_subject' => 'Bạn còn sản phẩm trong giỏ hàng',
    'default_body' => "Chào {{ customer_name }},\n\nGiỏ hàng của bạn còn {{ item_count }} sản phẩm, tạm tính {{ subtotal }}. Số lượng có hạn, hoàn tất đơn tại:\n{{ cart_url }}\n\nCảm ơn bạn đã ghé cửa hàng.",
];
