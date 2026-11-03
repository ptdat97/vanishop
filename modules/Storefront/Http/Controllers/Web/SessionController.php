<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Web;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Modules\Storefront\Application\MemberPrices;
use Modules\Storefront\Application\NativeCart;
use Modules\Storefront\Application\PriceVisibility;
use Modules\Storefront\Application\ProductViews;

/**
 * Phần riêng của khách cho trang công khai đã cache (storefront §5): đăng nhập, số món trong giỏ, thông báo flash và lỗi
 * form của lần gửi trước (đọc xong là hết, như flash thường). Không bao giờ cache.
 */
final class SessionController
{
    /**
     * Giá riêng của khách đăng nhập cho trang đang xem: `?v[]=1&v[]=2` → giá thành viên theo variant (chỉ khi nhóm khách có giá
     * thấp hơn); `?p[]=3` → khoảng giá chung theo sản phẩm khi cửa hàng ẩn giá với khách chưa đăng nhập (`products`).
     */
    public function prices(Request $request, MemberPrices $prices, PriceVisibility $visibility, ProductViews $products): JsonResponse
    {
        $customer = $request->attributes->get('customer');
        if ($customer === null) {
            return response()->json(['group' => null, 'prices' => [], 'products' => []])->header('Cache-Control', 'private, no-store');
        }

        $variantIds = array_map('intval', (array) $request->query('v', []));
        $styleIds = array_values(array_filter(array_map('intval', (array) $request->query('p', [])), fn (int $id): bool => $id > 0));

        return response()->json([
            ...$prices->for((int) $customer->id, $variantIds),
            'products' => $visibility->membersOnly() && $styleIds !== [] ? $products->priceRanges($styleIds, App::getLocale()) : [],
        ])->header('Cache-Control', 'private, no-store');
    }

    public function __invoke(Request $request, NativeCart $cart): JsonResponse
    {
        $customer = $request->attributes->get('customer');
        $errors = $request->session()->get('errors');
        $bag = $errors instanceof ViewErrorBag ? $errors->getBag('default') : new MessageBag;

        return response()->json([
            'signed_in' => $customer !== null,
            'account' => [
                'label' => $customer !== null ? 'Tài khoản' : 'Đăng nhập',
                'url' => $customer !== null ? route('storefront.account') : route('storefront.account.login'),
            ],
            'cart' => ['count' => $cart->current()->itemCount ?? 0],
            'prices_url' => route('storefront.session.prices'),
            'flash' => [
                'status' => $request->session()->get('status'),
                'errors' => array_map(fn (array $messages): string => (string) ($messages[0] ?? ''), $bag->toArray()),
            ],
            'cart_change' => $request->session()->get(CartController::CHANGE_FLASH),
        ])->header('Cache-Control', 'private, no-store');
    }
}
