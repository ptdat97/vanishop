<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Customer\Http\Middleware\AuthenticateCustomer;
use Modules\Shared\Support\LocalizedUrls;
use Modules\Tenancy\Contracts\Settings;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trang checkout của Blade khi cửa hàng tắt mua không đăng nhập (`storefront.guest_checkout`): khách chưa đăng nhập được
 * chuyển sang đăng nhập rồi quay lại — trước cả khi validate form, để khách không phải điền rồi mới bị từ chối. Chỉ là
 * trải nghiệm của Blade; quy tắc thật nằm ở Checkout và áp cho mọi kênh (API trả `checkout.sign_in_required`).
 * Chạy SAU middleware nhận diện khách (`vani.customer-session`).
 */
final class RequireCheckoutSignIn
{
    public function __construct(private readonly Settings $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->attributes->has(AuthenticateCustomer::ATTRIBUTE) || (bool) $this->settings->get('core', 'storefront.guest_checkout', true)) {
            return $next($request);
        }

        return redirect()->guest(LocalizedUrls::route('storefront.account.login'))->with('status', __('storefront::messages.sign_in_to_checkout'));
    }
}
