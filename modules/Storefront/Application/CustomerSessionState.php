<?php

declare(strict_types=1);

namespace Modules\Storefront\Application;

use Illuminate\Http\Request;
use Modules\Shared\Support\LocalizedUrls;

/**
 * Phần riêng của khách trên trang (đăng nhập, giỏ mini) — một nguồn cho cả hai chế độ (storefront §5):
 * không CDN thì layout render sẵn + nhúng cho JS; có CDN thì JS lấy qua `GET /_vani/phien`. Hai chế độ hiển thị như nhau.
 */
final class CustomerSessionState
{
    public function __construct(
        private readonly NativeCart $cart,
        private readonly CartPresenter $carts,
    ) {}

    /**
     * `csrf_token` (0.3.76): JS của theme/plugin gửi POST từ trang cache CDN (HTML không có token).
     *
     * @return array{signed_in: bool, account: array{label: string, url: string}, cart: array{count: int, subtotal: array<string, mixed>|null, lines: list<array<string, mixed>>}, prices_url: string, csrf_token: string|null}
     */
    public function for(Request $request, string $locale): array
    {
        $customer = $request->attributes->get('customer');
        $current = $this->cart->current();

        return [
            'signed_in' => $customer !== null,
            'account' => [
                'label' => $customer !== null ? __('Tài khoản') : __('Đăng nhập'),
                'url' => LocalizedUrls::route($customer !== null ? 'storefront.account' : 'storefront.account.login', [], $locale),
            ],
            'cart' => $current === null ? ['count' => 0, 'subtotal' => null, 'lines' => []] : $this->carts->mini($current),
            // Giá riêng của khách: Storefront API (gọi bằng phiên cùng nguồn, ADR-044).
            'prices_url' => route('api.storefront.v1.me.prices'),
            'csrf_token' => $request->hasSession() ? $request->session()->token() : null,
        ];
    }
}
