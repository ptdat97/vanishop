<?php

declare(strict_types=1);

namespace Plugin\Wishlist\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Plugin\Wishlist\Infrastructure\Wishlists;

/**
 * Yêu thích trên storefront native: trạng thái cho JS, thêm/bỏ (JSON hoặc form không JS), gộp danh sách của trình duyệt,
 * trang trong tài khoản.
 */
final class WishlistController
{
    public function __construct(private readonly Wishlists $wishlists) {}

    public function ids(Request $request): JsonResponse
    {
        $customerId = $this->customerId($request);

        return response()->json(['signed_in' => $customerId !== null, 'ids' => $customerId === null ? [] : $this->wishlists->ids($customerId)])
            ->header('Cache-Control', 'private, no-store');
    }

    public function toggle(Request $request): JsonResponse|RedirectResponse
    {
        $styleId = (int) $request->validate(['style_id' => ['required', 'integer', 'min:1']])['style_id'];
        $customerId = $this->customerId($request);
        if ($customerId === null) {
            return $request->expectsJson()
                ? response()->json(['message' => __('Đăng nhập để lưu sản phẩm yêu thích.')], 401)
                : redirect()->to(vani_route('storefront.account.login'));
        }

        $added = $this->wishlists->toggle($customerId, $styleId);
        $message = match ($added) {
            true => __('Đã thêm vào yêu thích.'),
            false => __('Đã bỏ khỏi yêu thích.'),
            null => __('Không thêm được: sản phẩm không còn bán hoặc danh sách đã đủ :max sản phẩm.', ['max' => Wishlists::MAX]),
        };

        return $request->expectsJson()
            ? response()->json(['in_wishlist' => $added === true, 'count' => count($this->wishlists->ids($customerId)), 'message' => $message], $added === null ? 422 : 200)
            : back()->with('status', $message);
    }

    public function merge(Request $request): JsonResponse
    {
        $data = $request->validate(['style_ids' => ['present', 'array', 'max:'.Wishlists::MAX], 'style_ids.*' => ['integer', 'min:1']]);
        $customerId = $this->customerId($request);
        abort_if($customerId === null, 401);

        $added = $this->wishlists->add($customerId, array_values(array_map('intval', $data['style_ids'])));

        return response()->json(['added' => $added, 'ids' => $this->wishlists->ids($customerId)]);
    }

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
