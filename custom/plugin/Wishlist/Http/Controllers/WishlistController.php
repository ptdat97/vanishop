<?php

declare(strict_types=1);

namespace Plugin\Wishlist\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Plugin\Wishlist\Infrastructure\Wishlists;

/**
 * Trang yêu thích trong tài khoản khách (thêm/bỏ/gộp: Storefront API — WishlistApiController).
 */
final class WishlistController
{
    public function __construct(private readonly Wishlists $wishlists) {}

    public function index(Request $request): View
    {
        return view('vani-wishlist::account', ['items' => $this->wishlists->cards((int) $this->customerId($request))]);
    }

    private function customerId(Request $request): ?int
    {
        $customer = $request->attributes->get('customer');

        return $customer === null ? null : (int) $customer->id;
    }
}
