<?php

declare(strict_types=1);

namespace Modules\Storefront\View\Composers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Extension\Contracts\AccountPages;
use Modules\Shared\Support\LocalizedUrls;

/**
 * `theme::partials.account-menu` (và `account-menu-links` nó include): `$accountLinks` = trang tài khoản của Core kèm URL
 * theo ngôn ngữ và trạng thái đang mở; `$accountMenu` = trang tài khoản của plugin.
 */
final class AccountMenuComposer
{
    private const PAGES = ['storefront.account' => 'Tổng quan', 'storefront.account.orders' => 'Đơn hàng', 'storefront.account.addresses' => 'Địa chỉ', 'storefront.account.profile' => 'Hồ sơ'];

    public function __construct(
        private readonly Request $request,
        private readonly AccountPages $pages,
    ) {}

    public function compose(View $view): void
    {
        $links = [];
        foreach (self::PAGES as $route => $label) {
            $links[] = [
                'url' => LocalizedUrls::route($route), 'label' => __($label),
                'active' => $this->request->routeIs($route, str_replace('storefront.', 'storefront.*.', $route)),
            ];
        }

        $view->with(['accountLinks' => $links, 'accountMenu' => $this->pages->active()]);
    }
}
