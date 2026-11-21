<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'flows' => [
        'check_expected_total' => 'Compare expected_total with the computed total (mismatch: checkout.totals_changed)',
        'claim_idempotency' => 'Claim the Idempotency-Key (duplicate: replay stored response)',
        'close_cart' => 'Mark cart lines ordered, store idempotent response, commit',
        'create_order' => 'Create order',
        'create_payment' => 'Create payment',
        'guest_checkout' => 'Check guest checkout (core.storefront.guest_checkout)',
        'load_cart' => 'Read cart with current prices',
        'lock_cart' => 'Open transaction, lock cart, re-read prices',
        'place_order' => 'Place order (PlaceOrder)',
        'quote' => 'Checkout preview (totals, shipping and payment options)',
        'record_promotions' => 'Record promotion usage',
        'reserve_stock' => 'Reserve stock per variant',
        'resolve_customer' => 'Find/create the customer profile by phone (outside the transaction to avoid deadlocks)',
    ],
    'checkout_service_provider' => [
        'allow_guest_checkout' => 'Allow guest checkout',
        'misplaced_calculators' => 'Plugin TotalsCalculator in the wrong place: :calculators — plugins use priority 300–399 (after promotions, before shipping) and a unique code.',
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
