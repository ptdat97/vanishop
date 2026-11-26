<?php

declare(strict_types=1);

namespace Plugin\Wishlist\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

    public function guest(Request $request): Response|RedirectResponse
    {
        return $this->customerId($request) === null
            ? response()->view('vani-wishlist::guest')->header('Cache-Control', 'private, no-store')
            : redirect()->to(vani_route('storefront.p.vani-wishlist.account.index'));
    }

    public function preview(Request $request): Response
    {
        $validated = $request->validate(['product_ids' => ['present', 'array', 'list', 'max:'.Wishlists::MAX], 'product_ids.*' => ['required', 'integer', 'distinct', 'min:1']]);
        $ids = array_map('intval', $validated['product_ids']);
        $cards = $this->wishlists->cardsFor($ids);
        $items = array_map(fn (int $id): array => ['product_id' => $id, 'card' => $cards[$id] ?? null], $ids);

        return response()->view('vani-wishlist::items', ['items' => $items, 'layout' => [1152, 3, 2]])->header('Cache-Control', 'private, no-store');
    }

    private function customerId(Request $request): ?int
    {
        $customer = $request->attributes->get('customer');

        return $customer === null ? null : (int) $customer->id;
    }
}
