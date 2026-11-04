<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Web;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Modules\Shared\Support\StoreLocale;
use Modules\Storefront\Application\CustomerSessionState;
use Modules\Storefront\Application\MemberPrices;
use Modules\Storefront\Application\PriceVisibility;
use Modules\Storefront\Application\ProductViews;

/**
 * Phần riêng của khách cho trang công khai đã cache CDN (storefront §5, chế độ tuỳ chọn): đăng nhập, giỏ mini, thông báo
 * flash và lỗi form của lần gửi trước (đọc xong là hết, như flash thường). Không bao giờ cache. Không CDN: layout render sẵn
 * cùng dữ liệu (CustomerSessionState).
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

    public function __invoke(Request $request, CustomerSessionState $state): JsonResponse
    {
        $locale = StoreLocale::isSupported((string) $request->query('locale')) ? (string) $request->query('locale') : StoreLocale::default();
        // Endpoint chỉ có ở ngôn ngữ mặc định; trang gọi truyền `?locale=` để nhãn và link tài khoản đúng ngôn ngữ trang.
        App::setLocale($locale);
        $errors = $request->session()->get('errors');
        $bag = $errors instanceof ViewErrorBag ? $errors->getBag('default') : new MessageBag;

        return response()->json([
            ...$state->for($request, $locale),
            'flash' => [
                'status' => $request->session()->get('status'),
                'errors' => array_map(fn (array $messages): string => (string) ($messages[0] ?? ''), $bag->toArray()),
            ],
            'cart_change' => $request->session()->get(CartController::CHANGE_FLASH),
        ])->header('Cache-Control', 'private, no-store');
    }
}
