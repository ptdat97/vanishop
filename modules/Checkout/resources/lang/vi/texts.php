<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'checkout_service_provider' => [
        'allow_guest_checkout' => 'Cho phép khách vãng lai đặt hàng',
        'off_customers_must_sign_place' => 'Tắt: phải đăng nhập mới đặt được hàng — trang thanh toán chuyển sang đăng nhập, Storefront API trả 403 `checkout.sign_in_required`. Xem giá và thêm giỏ vẫn không cần đăng nhập.',
        'shipping_fee' => 'Phí giao hàng',
        'store_s_taxcalculator_if_chosen' => 'TaxCalculator của cửa hàng. Implementation đã chọn không có hiệu lực (plugin tắt) → mặc định, rồi `none`.',
        'tax_calculation' => 'Cách tính thuế',
    ],
    'replacement_order_service' => [
        'compensation_returned_goods_reference' => 'Bù giá trị hàng trả (:reference)',
        'exchange_order_number_reference' => 'Đơn đổi hàng cho :number (:reference)',
    ],
    'replacement_unavailable' => [
        'exchanged_product_no_longer_sold' => 'Sản phẩm đổi không còn bán (variant ',
        'no_cash_delivery_gateway_enabled' => 'Không có cổng thu tiền khi giao đang bật để thu phần chênh.',
        'not_enough_stock_exchange_variant' => 'Không đủ hàng để đổi (variant ',
    ],
];
