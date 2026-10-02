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
    'vani.storefront.header.nav' => [
        'type' => 'slot',
        'visibility' => 'public',
        'since' => '0.3.1',
        'args' => [],
        'description' => 'Mục bổ sung trong menu chính (trang của plugin, bộ sưu tập động).',
    ],
    'vani.storefront.header.actions' => [
        'type' => 'slot',
        'visibility' => 'public',
        'since' => '0.3.1',
        'args' => [],
        'description' => 'Cụm thao tác ở header cạnh giỏ hàng (wishlist, điểm thưởng).',
    ],
    'vani.storefront.footer.columns' => [
        'type' => 'slot',
        'visibility' => 'public',
        'since' => '0.3.1',
        'args' => [],
        'description' => 'Cột bổ sung ở footer (chính sách, đăng ký nhận tin).',
    ],
    'vani.storefront.plp.filters' => [
        'type' => 'slot',
        'visibility' => 'public',
        'since' => '0.3.1',
        'args' => ['listing' => 'array<string, mixed> (ProductViews::listing)'],
        'description' => 'Bộ lọc bổ sung ở trang danh sách; dùng link/form GET, không cần JS.',
    ],
    'vani.storefront.pdp.gallery_after' => [
        'type' => 'slot',
        'visibility' => 'public',
        'since' => '0.3.1',
        'args' => ['product' => 'array<string, mixed> (ProductViews::detail)'],
        'description' => 'Dưới ảnh sản phẩm (video, ảnh 360°).',
    ],
    'vani.storefront.checkout.contact_after' => [
        'type' => 'slot',
        'visibility' => 'public',
        'since' => '0.3.1',
        'args' => ['quote' => 'array<string, mixed> (CheckoutPresenter::quote)'],
        'description' => 'Sau thông tin người nhận; trường của plugin đặt tên extra[<plugin id>][<field>].',
    ],
    'vani.storefront.checkout.address_after' => [
        'type' => 'slot',
        'visibility' => 'public',
        'since' => '0.3.1',
        'args' => ['quote' => 'array<string, mixed> (CheckoutPresenter::quote)'],
        'description' => 'Sau địa chỉ nhận hàng; trường của plugin đặt tên extra[<plugin id>][<field>].',
    ],
    'vani.storefront.checkout.payment_after' => [
        'type' => 'slot',
        'visibility' => 'public',
        'since' => '0.3.1',
        'args' => ['quote' => 'array<string, mixed> (CheckoutPresenter::quote)'],
        'description' => 'Sau chọn phương thức thanh toán (ưu đãi theo cổng, hướng dẫn).',
    ],
    'vani.storefront.pdp.add_to_cart_fields' => [
        'type' => 'slot',
        'visibility' => 'public',
        'since' => '0.3.4',
        'args' => ['product' => 'array<string, mixed> (ProductViews::detail)'],
        'description' => 'Trường trong form thêm giỏ (trước nút): tuỳ chọn dòng của plugin, đặt tên options[<plugin id>][<field>] (CartLineOption).',
    ],
];
