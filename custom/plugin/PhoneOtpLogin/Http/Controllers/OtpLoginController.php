<?php

declare(strict_types=1);

namespace Plugin\PhoneOtpLogin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Customer\Contracts\PhoneOtpLogin;
use Modules\Shared\Domain\BusinessRuleViolation;
use Modules\Storefront\Contracts\CustomerSignIn;

/**
 * Form OTP trên trang đăng nhập của storefront native: gửi mã → ô nhập mã hiện ra (SĐT giữ trong phiên) → đăng nhập vào
 * phiên web (gộp giỏ vãng lai). Lỗi hiện ngay dưới form OTP (túi lỗi `otp`), không lẫn với form email.
 */
final class OtpLoginController
{
    public const PENDING_PHONE = 'vani-phone-otp-login.phone';

    public function __construct(private readonly PhoneOtpLogin $otp) {}

    public function request(Request $request): RedirectResponse
    {
        $phone = (string) $request->validateWithBag('otp', ['phone' => ['required', 'string', 'max:20']])['phone'];
        try {
            $this->otp->requestCode($phone, (string) $request->ip());
        } catch (BusinessRuleViolation $exception) {
            return $this->back()->withInput()->withErrors(['phone' => $exception->getMessage()], 'otp');
        }
        $request->session()->put(self::PENDING_PHONE, $phone);

        return $this->back()->with('status', __('Đã gửi mã xác thực. Vui lòng kiểm tra tin nhắn.'));
    }

    public function verify(Request $request, CustomerSignIn $signIn): RedirectResponse
    {
        $code = (string) $request->validateWithBag('otp', ['code' => ['required', 'string', 'max:10']])['code'];
        $phone = (string) $request->session()->get(self::PENDING_PHONE, '');
        if ($phone === '') {
            return $this->back();
        }
        try {
            $session = $this->otp->login($phone, $code, 'web');
        } catch (BusinessRuleViolation $exception) {
            return $this->back()->withErrors(['code' => $exception->getMessage()], 'otp');
        }
        $request->session()->forget(self::PENDING_PHONE);

        return redirect()->to($signIn->signIn($request, $session['token'], $session['customer']->id));
    }

    private function back(): RedirectResponse
    {
        return redirect()->to(vani_route('storefront.account.login'));
    }
}
