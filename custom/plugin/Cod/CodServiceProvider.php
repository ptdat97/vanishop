<?php

declare(strict_types=1);

namespace Plugin\Cod;

use Modules\Extension\PluginServiceProvider;
use Modules\Ordering\Events\OrderPlaced;
use Modules\Payment\Contracts\PaymentGateway;
use Plugin\Cod\Infrastructure\CodGateway;
use Plugin\Cod\Listeners\AutoConfirmCodOrder;

/**
 * Plugin hệ thống (ADR-029): thanh toán khi nhận hàng.
 */
final class CodServiceProvider extends PluginServiceProvider
{
    public const ID = 'vani.cod';

    protected function pluginId(): string
    {
        return self::ID;
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->pluginPath('Config/cod.php'), 'vani.cod');
    }

    public function boot(): void
    {
        $this->translations($this->pluginPath('resources/lang'), 'vani-cod');
        $this->settings([
            ['key' => 'max_amount', 'label' => 'Đơn COD tối đa (₫)', 'type' => 'int', 'help' => 'Để trống = theo VANI_COD_MAX_AMOUNT.'],
            ['key' => 'auto_confirm', 'label' => 'Tự xác nhận đơn COD', 'type' => 'bool', 'help' => 'Tắt để CSKH gọi xác nhận trước. Để trống = theo VANI_COD_AUTO_CONFIRM.'],
        ]);

        $this->contribute(PaymentGateway::TAG, CodGateway::class);
        $this->onEvent(OrderPlaced::class, AutoConfirmCodOrder::class);
    }
}
