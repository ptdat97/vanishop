<?php

declare(strict_types=1);

namespace Plugin\CouponCode\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Modules\Cart\Contracts\Data\CartKey;
use Modules\Catalog\Contracts\CatalogReader;
use Modules\Shared\Domain\BusinessRuleViolation;
use Modules\Storefront\Contracts\CurrentCart;
use Plugin\CouponCode\Infrastructure\CouponPresenter;

/**
 * Storefront API của mã giảm giá: ví voucher trên giỏ (giỏ định danh như API giỏ của Core: `carts/{cart}` +
 * `X-Vani-Cart-Token`), mã công khai của một sản phẩm, mã riêng của khách đăng nhập.
 */
final class CouponApiController
{
    private const CART_TOKEN_HEADER = 'X-Vani-Cart-Token';

    public function __construct(private readonly CouponPresenter $presenter) {}

    public function wallet(Request $request, string $cart, CurrentCart $shopper): JsonResponse
    {
        try {
            $wallet = $this->presenter->wallet(new CartKey($cart, (string) $request->headers->get(self::CART_TOKEN_HEADER, '')), $shopper->customerId(), App::getLocale(), now()->getTimestamp());
        } catch (BusinessRuleViolation $rejected) {
            return response()->json(['error' => ['code' => $rejected->errorCode(), 'message' => $rejected->getMessage()]], 404);
        }

        return response()->json(['data' => $wallet['items'], 'meta' => ['best' => $wallet['best']]]);
    }

    public function product(int $product, CatalogReader $catalog): JsonResponse
    {
        $now = now()->getTimestamp();
        $card = $catalog->productsByIds([$product], App::getLocale(), $now)[0] ?? null;
        abort_if($card === null, 404);

        return response()->json(['data' => $this->presenter->forProduct($card, App::getLocale(), $now)]);
    }

    public function mine(Request $request): JsonResponse
    {
        // Route có middleware `vani.customer`: khách đã đăng nhập (Bearer hoặc phiên web cùng nguồn).
        return response()->json(['data' => $this->presenter->mine((int) $request->attributes->get('customer')->id, App::getLocale(), now()->getTimestamp())]);
    }
}
