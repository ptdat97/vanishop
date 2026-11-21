<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'flows' => [
        'check_expected_total' => 'So expected_total với tổng vừa tính (lệch: checkout.totals_changed)',
        'claim_idempotency' => 'Giữ khoá Idempotency-Key (trùng: trả lại phản hồi cũ)',
        'close_cart' => 'Đánh dấu dòng giỏ đã đặt, lưu phản hồi idempotency, commit',
        'create_order' => 'Tạo đơn',
        'create_payment' => 'Tạo khoản thanh toán',
        'guest_checkout' => 'Kiểm khách vãng lai (core.storefront.guest_checkout)',
        'load_cart' => 'Đọc giỏ, giá hiện hành',
        'lock_cart' => 'Mở transaction, khoá giỏ, đọc lại giá',
        'place_order' => 'Đặt hàng (PlaceOrder)',
        'quote' => 'Xem trước checkout (tổng tiền, cách giao, cách thanh toán)',
        'record_promotions' => 'Ghi nhận lượt dùng khuyến mãi',
        'reserve_stock' => 'Giữ hàng theo variant',
        'resolve_customer' => 'Tìm/tạo hồ sơ khách theo SĐT (ngoài transaction để tránh deadlock)',
    ],
    'checkout_service_provider' => [
        'allow_guest_checkout' => 'Cho phép khách vãng lai đặt hàng',
        'misplaced_calculators' => 'TotalsCalculator của plugin đặt sai chỗ: :calculators — plugin dùng priority 300–399 (sau khuyến mãi, trước phí giao) và mã không trùng.',
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
