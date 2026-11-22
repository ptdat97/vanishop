<?php

declare(strict_types=1);

namespace Plugin\PhoneOtpLogin;

use Modules\Extension\PluginServiceProvider;
use Modules\Storefront\Contracts\Data\SlotView;
use Plugin\PhoneOtpLogin\Http\Controllers\OtpLoginController;

/**
 * Đăng nhập bằng mã OTP qua số điện thoại — phương thức **bổ sung** bên cạnh email + mật khẩu của Core (0.5.12). Core giữ khung
 * OTP và quy tắc tài khoản (contract `PhoneOtpLogin`: SĐT chưa có hồ sơ → tạo; hồ sơ khách vãng lai → thành tài khoản, giữ lịch
 * sử đơn); plugin thêm form vào trang đăng nhập (slot `vani.storefront.account.login`), route web `/p/vani-phone-otp-login/*`
 * và Storefront API `x/vani-phone-otp-login/*` (openapi.json). Mã gửi qua kênh OTP đang bật (vd. `vani.zalo-zns`,
 * hoặc email trên hồ sơ).
 */
final class PhoneOtpLoginServiceProvider extends PluginServiceProvider
{
    public const ID = 'vani.phone-otp-login';

    protected function pluginId(): string
    {
        return self::ID;
    }

    public function boot(): void
    {
        $this->translations($this->pluginPath('Resources/lang'), 'vani-phone-otp-login');
        $this->storefrontViews($this->pluginPath('Resources/views'), 'vani-phone-otp-login');
        $this->storefrontPages($this->pluginPath('Http/routes/pages.php'));
        $this->storefrontRoutes($this->pluginPath('Http/routes/storefront-api.php'));

        $this->onSlot('vani.storefront.account.login', fn (): SlotView => new SlotView('vani-phone-otp-login::login-form', [
            'phone' => session()->get(OtpLoginController::PENDING_PHONE),
        ]));
    }
}
