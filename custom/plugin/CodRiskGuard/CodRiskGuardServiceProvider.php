<?php

declare(strict_types=1);

namespace Plugin\CodRiskGuard;

use Modules\Customer\Contracts\CustomerNotes;
use Modules\Customer\Contracts\CustomerSegments;
use Modules\Extension\PluginServiceProvider;
use Modules\Fulfillment\Events\ShipmentStatusChanged;
use Modules\Ordering\Contracts\Data\OrderData;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Ordering\Contracts\OrderRiskSignal;
use Modules\Tenancy\Contracts\Settings;
use Plugin\CodRiskGuard\Infrastructure\RiskRules;

/**
 * Chống bom hàng COD (EP v3 X2 — tham chiếu cho `OrderRiskSignal` và hook `vani.order.before_confirm`): tín hiệu rủi ro
 * trên "Cần xác nhận"; chặn xác nhận đơn của SĐT trong danh sách đen (nhân viên lẫn tự xác nhận của `vani.cod`).
 */
final class CodRiskGuardServiceProvider extends PluginServiceProvider
{
    public const ID = 'vani.cod-risk-guard';

    protected function pluginId(): string
    {
        return self::ID;
    }

    public function boot(): void
    {
        $this->translations($this->pluginPath('Resources/lang'), 'vani-cod-risk-guard');
        $this->settings([
            ['key' => 'blacklist', 'label' => 'vani-cod-risk-guard::texts.cod_risk_guard_service_provider.phone_blacklist', 'type' => 'text', 'help' => 'vani-cod-risk-guard::texts.cod_risk_guard_service_provider.one_number_per_line_0912345678'],
            ['key' => 'block_blacklisted', 'label' => 'vani-cod-risk-guard::texts.cod_risk_guard_service_provider.block_confirming_orders_from_blacklisted', 'type' => 'bool', 'default' => true,
                'help' => 'vani-cod-risk-guard::texts.cod_risk_guard_service_provider.staff_must_remove_number_from'],
            ['key' => 'returned_threshold', 'label' => 'vani-cod-risk-guard::texts.cod_risk_guard_service_provider.returned_orders_count_high_risk', 'type' => 'int', 'default' => 2, 'rules' => ['min:1', 'max:20']],
            ['key' => 'tag_returned', 'label' => 'vani-cod-risk-guard::texts.cod_risk_guard_service_provider.tag_bom_hang_add_customer', 'type' => 'bool', 'default' => true],
        ]);
        // EP v3 X6: đơn hoàn về → nhãn khách + ghi chú CSKH (CustomerSegments/CustomerNotes, nguồn system:<plugin>).
        $this->onEvent(ShipmentStatusChanged::class, function (ShipmentStatusChanged $event): void {
            if ($event->to !== 'returned' || ! (bool) $this->app->make(Settings::class)->get(self::ID, 'tag_returned', true)) {
                return;
            }
            $order = $this->app->make(OrderReader::class)->find($event->orderId);
            if ($order?->customerId === null) {
                return;
            }
            $this->app->make(CustomerSegments::class)->tag($order->customerId, 'bom-hang', 'system:'.self::ID);
            $this->app->make(CustomerNotes::class)->add($order->customerId, __('vani-cod-risk-guard::texts.cod_risk_guard_service_provider.order_number_was_returned_sender', ['number' => $order->number]), 'system:'.self::ID);
        });
        $this->contribute(OrderRiskSignal::TAG, RiskRules::class);
        $this->onValidate('vani.order.before_confirm', function (OrderData $order): array {
            $settings = $this->app->make(Settings::class);
            if (! (bool) $settings->get(self::ID, 'block_blacklisted', true) || ! $this->app->make(RiskRules::class)->blacklisted($order)) {
                return [];
            }

            return [__('vani-cod-risk-guard::texts.cod_risk_guard_service_provider.phone_number_store_s_blacklist')];
        });
    }
}
