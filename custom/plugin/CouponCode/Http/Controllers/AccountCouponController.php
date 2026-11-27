<?php

declare(strict_types=1);

namespace Plugin\CouponCode\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Plugin\CouponCode\CouponCodeServiceProvider;
use Plugin\CouponCode\Infrastructure\CouponPresenter;

/** Tài khoản → Mã giảm giá của tôi: mã riêng còn dùng được, đã dùng, hết hạn. */
final class AccountCouponController
{
    public function __invoke(Request $request, CouponPresenter $presenter): View
    {
        return view('vani-coupon-code::account', [
            'vouchers' => $presenter->mine((int) $request->attributes->get('customer')->id, App::getLocale(), now()->getTimestamp()),
            'scriptSrc' => vani_asset(CouponCodeServiceProvider::ID, 'coupons.js'),
        ]);
    }
}
