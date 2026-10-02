<?php

/*
| Slot UI của native storefront (ADR-025) — public API, đổi/xoá vị trí là thay đổi major.
| Listener trả Modules\\Storefront\\Contracts\\Data\\SlotView (hoặc Htmlable); chỉ nối thêm, lỗi một listener bị bỏ qua.
| Theme render bằng <x-vani::hook-slot name="…" :args="[…]" />. Danh mục: docs/04-extension/extension-point-catalog.md §4.1
*/

return [
    'vani.storefront.layout.head' => [
        'type' => 'slot',
        'visibility' => 'public',
        'since' => '0.3',
        'args' => [],
        'description' => 'Cuối <head> mọi trang storefront (pixel tracking, meta xác minh).',
    ],
    'vani.storefront.layout.body_end' => [
        'type' => 'slot',
        'visibility' => 'public',
        'since' => '0.3',
        'args' => [],
        'description' => 'Cuối <body> mọi trang storefront (chat, script đo lường).',
    ],
    'vani.storefront.plp.card_badges' => [
        'type' => 'slot',
        'visibility' => 'public',
        'since' => '0.3',
        'args' => ['product' => 'array<string, mixed> (một phần tử ProductViews::listing items)'],
        'description' => 'Nhãn trên thẻ sản phẩm ở danh sách ("Mới", "Freeship").',
    ],
    'vani.storefront.pdp.after_title' => [
        'type' => 'slot',
        'visibility' => 'public',
        'since' => '0.3',
        'args' => ['product' => 'array<string, mixed> (ProductViews::detail)'],
        'description' => 'Dưới tên sản phẩm (đánh giá sao).',
    ],
    'vani.storefront.pdp.after_price' => [
        'type' => 'slot',
        'visibility' => 'public',
        'since' => '0.3',
        'args' => ['product' => 'array<string, mixed> (ProductViews::detail)'],
        'description' => 'Dưới giá (trả góp, điểm thưởng dự kiến).',
    ],
    'vani.storefront.pdp.after_add_to_cart' => [
        'type' => 'slot',
        'visibility' => 'public',
        'since' => '0.3',
        'args' => ['product' => 'array<string, mixed> (ProductViews::detail)'],
        'description' => 'Dưới nút thêm giỏ (bảng size, cam kết đổi trả).',
    ],
    'vani.storefront.pdp.after_details' => [
        'type' => 'slot',
        'visibility' => 'public',
        'since' => '0.3',
        'args' => ['product' => 'array<string, mixed> (ProductViews::detail)'],
        'description' => 'Sau mô tả (lookbook, sản phẩm gợi ý).',
    ],
    'vani.storefront.cart.after_lines' => [
        'type' => 'slot',
        'visibility' => 'public',
        'since' => '0.3',
        'args' => ['cart' => 'array<string, mixed> (CartPresenter::present)'],
        'description' => 'Sau danh sách dòng giỏ (tiến độ freeship, upsell).',
    ],
    'vani.storefront.checkout.after_shipping' => [
        'type' => 'slot',
        'visibility' => 'public',
        'since' => '0.3',
        'args' => ['quote' => 'array<string, mixed> (CheckoutPresenter::quote)'],
        'description' => 'Sau chọn phương thức giao (ghi chú giao hàng, gói quà).',
    ],
    'vani.storefront.checkout.before_submit' => [
        'type' => 'slot',
        'visibility' => 'public',
        'since' => '0.3',
        'args' => ['quote' => 'array<string, mixed> (CheckoutPresenter::quote)'],
        'description' => 'Trước nút đặt hàng; trường của plugin đặt tên extra[<plugin id>][<field>] (vd. xuất hoá đơn điện tử).',
    ],
    'vani.storefront.order.after_summary' => [
        'type' => 'slot',
        'visibility' => 'public',
        'since' => '0.3',
        'args' => ['order' => 'array<string, mixed> (OrderPresenter::present)'],
        'description' => 'Trang cảm ơn / chi tiết đơn (hướng dẫn chuyển khoản, điểm đã cộng).',
    ],
];
