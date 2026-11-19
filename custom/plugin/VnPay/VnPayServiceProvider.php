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
        $this->translations($this->pluginPath('Resources/lang'), 'vani-vnpay');
        $this->settings([
            ['key' => 'tmn_code', 'label' => 'vani-vnpay::texts.vn_pay_service_provider.website_code_vnp_tmncode', 'help' => 'vani-vnpay::texts.vn_pay_service_provider.leave_empty_use_vnpay_tmn'],
            ['key' => 'hash_secret', 'label' => 'vani-vnpay::texts.vn_pay_service_provider.secret_key_vnp_hashsecret', 'type' => 'secret', 'help' => 'vani-vnpay::texts.vn_pay_service_provider.stored_encrypted_leave_empty_use'],
            ['key' => 'sandbox', 'label' => 'vani-vnpay::texts.vn_pay_service_provider.test_environment_sandbox', 'type' => 'bool', 'help' => 'vani-vnpay::texts.vn_pay_service_provider.turn_off_production_leave_empty'],
            ['key' => 'ttl', 'label' => 'vani-vnpay::texts.vn_pay_service_provider.payment_timeout_seconds', 'type' => 'int', 'help' => 'vani-vnpay::texts.vn_pay_service_provider.least_300_leave_empty_use'],
        ]);

        $this->contribute(PaymentGateway::TAG, VnPayGateway::class);
        $this->storefrontPages($this->pluginPath('Http/routes/storefront-pages.php'));
    }
}
