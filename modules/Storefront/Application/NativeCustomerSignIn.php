<?php

declare(strict_types=1);

namespace Modules\Storefront\Application;

use Illuminate\Http\Request;
use Modules\Shared\Support\LocalizedUrls;
use Modules\Storefront\Contracts\CustomerSignIn;

final class NativeCustomerSignIn implements CustomerSignIn
{
    /** Khoá phiên chứa token khách — middleware `vani.customer-session` đọc cùng khoá. */
    public const TOKEN = 'vani.customer_token';

    public function __construct(private readonly NativeCart $cart) {}

    public function signIn(Request $request, string $token, int $customerId): string
    {
        $request->session()->regenerate();
        $request->session()->put(self::TOKEN, $token);
        $this->cart->attachGuestCartTo($customerId);

        return (string) $request->session()->pull('url.intended', LocalizedUrls::route('storefront.account'));
    }
}
