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

    /** Id mẫu trong danh sách — JS tô nút tim (nhẹ hơn `items`, không dựng thẻ sản phẩm). Không cache. */
    public function ids(Request $request): JsonResponse
    {
        return response()->json(['data' => ['ids' => $this->wishlists->ids($this->customerId($request))]])->header('Cache-Control', 'private, no-store');
    }

    /** Gộp danh sách của trình duyệt (khách vãng lai lưu ở localStorage) khi đăng nhập: bỏ trùng, bỏ mẫu không bán, giữ giới hạn. */
    public function merge(Request $request): JsonResponse
    {
        $data = $request->validate(['style_ids' => ['present', 'array', 'max:'.Wishlists::MAX], 'style_ids.*' => ['integer', 'min:1']]);
        $added = $this->wishlists->add($this->customerId($request), array_values(array_map('intval', $data['style_ids'])));

        return response()->json(['data' => ['added' => $added, 'ids' => $this->wishlists->ids($this->customerId($request))]]);
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
