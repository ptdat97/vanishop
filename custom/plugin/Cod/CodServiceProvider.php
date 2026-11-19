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
            ['key' => 'max_amount', 'label' => 'vani-cod::texts.cod_service_provider.maximum_cod_order', 'type' => 'int', 'help' => 'vani-cod::texts.cod_service_provider.leave_empty_use_vani_cod'],
            ['key' => 'auto_confirm_mode', 'label' => 'vani-cod::texts.cod_service_provider.auto_confirm_cod_orders', 'type' => 'select', 'options' => [
                'off' => 'vani-cod::texts.cod_service_provider.no_staff_call_message_confirm',
                'trusted' => 'vani-cod::texts.cod_service_provider.returning_customers_only_received_orders',
                'all' => 'vani-cod::texts.cod_service_provider.yes_confirm_every_cod_order',
            ], 'help' => 'vani-cod::texts.cod_service_provider.returning_customers_identified_phone_number'],
            ['key' => 'trusted_min_delivered', 'label' => 'vani-cod::texts.cod_service_provider.returning_customer_minimum_delivered_orders', 'type' => 'int', 'default' => 1, 'rules' => ['min:1', 'max:100'], 'help' => 'vani-cod::texts.cod_service_provider.used_returning_customers_only'],
        ]);

        $this->contribute(PaymentGateway::TAG, CodGateway::class);
        $this->onEvent(OrderPlaced::class, AutoConfirmCodOrder::class);
    }
}
