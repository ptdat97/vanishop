<?php

declare(strict_types=1);

namespace Plugin\Wishlist\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugin\Wishlist\Infrastructure\Wishlists;

/**
 * Storefront API (Bearer) cho app: danh sách (thẻ sản phẩm; mẫu không còn bán có `product: null`), thêm, bỏ.
 */
final class WishlistApiController
{
    public function __construct(private readonly Wishlists $wishlists) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => array_map(
            fn (array $item): array => ['style_id' => $item['style_id'], 'product' => $item['card']],
            $this->wishlists->cards($this->customerId($request)),
        )]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['style_id' => ['required', 'integer', 'min:1']]);
        $customerId = $this->customerId($request);
        if (! in_array((int) $data['style_id'], $this->wishlists->ids($customerId), true) && $this->wishlists->add($customerId, [(int) $data['style_id']]) === 0) {
            return response()->json(['error' => ['code' => 'wishlist.unavailable', 'message' => __('Không thêm được: sản phẩm không còn bán hoặc danh sách đã đủ :max sản phẩm.', ['max' => Wishlists::MAX])]], 422);
        }

        return response()->json(['data' => ['ids' => $this->wishlists->ids($customerId)]], 201);
    }

    public function destroy(Request $request, int $style): JsonResponse
    {
        $this->wishlists->remove($this->customerId($request), $style);

        return response()->json(['data' => ['ids' => $this->wishlists->ids($this->customerId($request))]]);
    }

    private function customerId(Request $request): int
    {
        return (int) $request->attributes->get('customer')->id;
    }
}
