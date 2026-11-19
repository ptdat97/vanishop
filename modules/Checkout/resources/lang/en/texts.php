<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'checkout_service_provider' => [
        'allow_guest_checkout' => 'Allow guest checkout',
        'off_customers_must_sign_place' => 'Off: customers must sign in to place an order — the checkout page redirects to sign-in, the Storefront API returns 403 `checkout.sign_in_required`. Viewing prices and adding to cart still need no sign-in.',
        'shipping_fee' => 'Shipping fee',
        'store_s_taxcalculator_if_chosen' => 'The store\'s TaxCalculator. If the chosen implementation is not active (plugin disabled) → the default, then `none`.',
        'tax_calculation' => 'Tax calculation',
    ],
    'replacement_order_service' => [
        'compensation_returned_goods_reference' => 'Compensation for returned goods (:reference)',
        'exchange_order_number_reference' => 'Exchange order for :number (:reference)',
    ],
    'replacement_unavailable' => [
        'exchanged_product_no_longer_sold' => 'The exchanged product is no longer sold (variant',
        'no_cash_delivery_gateway_enabled' => 'No cash-on-delivery gateway is enabled to collect the difference.',
        'not_enough_stock_exchange_variant' => 'Not enough stock to exchange (variant',
    ],
];
