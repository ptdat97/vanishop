<?php

declare(strict_types=1);

namespace Plugin\VnPay;

use Modules\Extension\PluginServiceProvider;
use Modules\Payment\Contracts\PaymentGateway;
use Plugin\VnPay\Infrastructure\VnPayGateway;

/**
 * Cổng thanh toán VNPay. Cấu hình: Admin → Cấu hình (vani.vnpay), mặc định từ .env (VNPAY_*).
 * IPN đăng ký với VNPay: https://<domain>/api/payments/vnpay/callback.
 */
final class VnPayServiceProvider extends PluginServiceProvider
{
    public const ID = 'vani.vnpay';

    protected function pluginId(): string
    {
        return self::ID;
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->pluginPath('Config/vnpay.php'), 'vani.vnpay');
    }

    public function boot(): void
    {
        $this->settings([
            ['key' => 'tmn_code', 'label' => 'Mã website (vnp_TmnCode)', 'help' => 'Để trống = theo VNPAY_TMN_CODE.'],
            ['key' => 'hash_secret', 'label' => 'Khoá bí mật (vnp_HashSecret)', 'type' => 'secret', 'help' => 'Lưu mã hoá. Để trống = theo VNPAY_HASH_SECRET.'],
            ['key' => 'sandbox', 'label' => 'Môi trường thử nghiệm (sandbox)', 'type' => 'bool', 'help' => 'Tắt khi chạy thật. Để trống = theo VNPAY_SANDBOX.'],
            ['key' => 'ttl', 'label' => 'Thời gian chờ thanh toán (giây)', 'type' => 'int', 'help' => 'Tối thiểu 300. Để trống = theo VNPAY_TTL (900).'],
        ]);

        $this->contribute(PaymentGateway::TAG, VnPayGateway::class);
        $this->storefrontPages($this->pluginPath('Http/routes/storefront-pages.php'));
    }
}
