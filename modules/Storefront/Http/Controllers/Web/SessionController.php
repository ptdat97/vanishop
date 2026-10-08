<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Web;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Modules\Storefront\Application\MemberPrices;
use Modules\Storefront\Application\NativeCart;

/**
 * Phần riêng của khách cho trang công khai đã cache (storefront §5): đăng nhập, số món trong giỏ, thông báo flash và lỗi
 * form của lần gửi trước (đọc xong là hết, như flash thường). Không bao giờ cache.
 */
final class SessionController
{
    /**
     * Giá thành viên cho các variant trên trang đang xem (`?v[]=1&v[]=2`) — chỉ khi khách đăng nhập thuộc nhóm có giá riêng.
     */
    public function prices(Request $request, MemberPrices $prices): JsonResponse
    {
        $customer = $request->attributes->get('customer');
        $ids = array_map('intval', (array) $request->query('v', []));

        return response()->json($customer === null ? ['group' => null, 'prices' => []] : $prices->for((int) $customer->id, $ids))
            ->header('Cache-Control', 'private, no-store');
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
            'flash' => [
                'status' => $request->session()->get('status'),
                'errors' => array_map(fn (array $messages): string => (string) ($messages[0] ?? ''), $bag->toArray()),
            ],
        ])->header('Cache-Control', 'private, no-store');
    }
}
