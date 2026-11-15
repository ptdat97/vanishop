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
        $this->settings([
            ['key' => 'blacklist', 'label' => 'Danh sách đen SĐT', 'type' => 'text', 'help' => 'Mỗi dòng một số, vd. 0912345678. Đơn COD của các số này bị đánh dấu rủi ro cao.'],
            ['key' => 'block_blacklisted', 'label' => 'Chặn xác nhận đơn của SĐT trong danh sách đen', 'type' => 'bool', 'default' => true,
                'help' => 'Nhân viên phải gỡ số khỏi danh sách đen (sau khi gọi xác nhận) mới xác nhận được.'],
            ['key' => 'returned_threshold', 'label' => 'Số đơn hoàn về để coi là rủi ro cao', 'type' => 'int', 'default' => 2, 'rules' => ['min:1', 'max:20']],
            ['key' => 'tag_returned', 'label' => 'Gắn nhãn "bom-hang" và ghi chú CSKH khi đơn của khách bị hoàn về', 'type' => 'bool', 'default' => true],
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
            $this->app->make(CustomerNotes::class)->add($order->customerId, "Đơn {$order->number} bị hoàn về (khách không nhận).", 'system:'.self::ID);
        });
        $this->contribute(OrderRiskSignal::TAG, RiskRules::class);
        $this->onValidate('vani.order.before_confirm', function (OrderData $order): array {
            $settings = $this->app->make(Settings::class);
            if (! (bool) $settings->get(self::ID, 'block_blacklisted', true) || ! $this->app->make(RiskRules::class)->blacklisted($order)) {
                return [];
            }

            return ['SĐT trong danh sách đen của cửa hàng — gọi xác nhận với khách rồi gỡ số khỏi danh sách đen (Cấu hình → Chống bom hàng COD)'];
        });
    }
}
