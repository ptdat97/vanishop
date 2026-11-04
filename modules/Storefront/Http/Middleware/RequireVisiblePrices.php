<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Modules\Shared\Support\LocalizedUrls;
use Modules\Storefront\Application\PriceVisibility;
use Symfony\Component\HttpFoundation\Response;

/**
 * Giỏ và checkout khi cửa hàng chỉ hiện giá cho thành viên (`storefront.price_visibility = members`): khách chưa đăng
 * nhập bị chặn — API 403 `storefront.sign_in_to_view_prices`, web chuyển sang đăng nhập rồi quay lại. Chạy SAU middleware
 * nhận diện khách (`vani.customer-session`, `vani.customer:optional`).
 */
final class RequireVisiblePrices
{
    public function __construct(private readonly PriceVisibility $visibility) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->visibility->hidden()) {
            return $next($request);
        }

        $message = __('storefront::messages.sign_in_to_view_prices');
        if ($request->is('api/*') || $request->expectsJson()) {
            return new JsonResponse(['error' => array_filter([
                'code' => 'storefront.sign_in_to_view_prices',
                'message' => $message,
                'correlation_id' => Context::get('correlation_id'),
            ])], 403);
        }

        // Thêm giỏ từ trang sản phẩm: đăng nhập xong quay lại trang đó, không phải URL POST.
        $intended = $request->isMethod('GET') ? $request->fullUrl() : url()->previous(LocalizedUrls::route('storefront.home'));
        $request->session()->put('url.intended', $intended);

        return redirect()->to(LocalizedUrls::route('storefront.account.login'))->with('status', $message);
    }
}
