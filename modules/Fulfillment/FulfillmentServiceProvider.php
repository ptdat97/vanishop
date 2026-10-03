<?php

declare(strict_types=1);

namespace Modules\Fulfillment;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Modules\Extension\Application\Admin\AdminNavigation;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Contracts\Requirement;
use Modules\Extension\Facades\Hook;
use Modules\Fulfillment\Application\CarrierRegistry;
use Modules\Fulfillment\Application\Carriers\ManualCarrier;
use Modules\Fulfillment\Application\EloquentShipmentReader;
use Modules\Fulfillment\Application\Listeners\CancelShipmentsOnOrderCancel;
use Modules\Fulfillment\Application\Listeners\CreateShipmentsOnConfirm;
use Modules\Fulfillment\Application\Listeners\OrderShipmentPanel;
use Modules\Fulfillment\Application\ReservedLocationSourcing;
use Modules\Fulfillment\Console\CompleteDeliveredOrdersCommand;
use Modules\Fulfillment\Contracts\ShipmentReader;
use Modules\Fulfillment\Contracts\ShippingCarrier;
use Modules\Fulfillment\Contracts\SourcingStrategy;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Identity\Application\PermissionRegistry;
use Modules\Ordering\Events\OrderCancelled;
use Modules\Ordering\Events\OrderConfirmed;
use Modules\Shared\Support\ModuleServiceProvider;
use Modules\Tenancy\Contracts\Data\SettingDefinition;
use Modules\Tenancy\Contracts\Settings;

final class FulfillmentServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Fulfillment';
    }

    public function register(): void
    {
        $this->app->bind(ShipmentReader::class, EloquentShipmentReader::class);
        $this->app->make(Extensions::class)->tag([ManualCarrier::class], CarrierRegistry::CARRIERS_TAG);
        $this->app->make(Extensions::class)->requires(ShippingCarrier::CARRIERS_TAG, Requirement::AtLeastOne, 'Hãng vận chuyển');
        $this->app->make(Extensions::class)->kindContract('shipping_carrier', ShippingCarrier::CARRIERS_TAG);
        $this->app->make(Extensions::class)->tag([ReservedLocationSourcing::class], SourcingStrategy::TAG);
        $this->app->make(Extensions::class)->requires(SourcingStrategy::TAG, Requirement::ExactlyOne, 'Chọn kho xuất hàng');
    }

    public function boot(PermissionRegistry $permissions, AdminNavigation $navigation): void
    {
        $this->app->make(Settings::class)->define(new SettingDefinition(
            'core', 'fulfillment.sourcing', 'Chọn kho giao hàng (sourcing)', 'select', (string) config('vanishop.fulfillment.sourcing', 'reserved_locations'),
            optionsFromTag: SourcingStrategy::TAG, help: 'SourcingStrategy khi tạo vận đơn.',
        ));
        $permissions->register('fulfillment.view', 'Xem vận đơn');
        $permissions->register('fulfillment.manage', 'Tạo/cập nhật/huỷ vận đơn');

        $navigation->add('fulfillment', 'Giao hàng', 'admin.fulfillment.home', 'fulfillment.view', 60);

        Event::listen(OrderConfirmed::class, CreateShipmentsOnConfirm::class);
        Event::listen(OrderCancelled::class, CancelShipmentsOnOrderCancel::class);
        Hook::onSlot('vani.admin.order.sidebar', fn ($order) => $this->app->make(OrderShipmentPanel::class)($order), priority: 5);

        // Tắt hãng còn vận đơn chưa kết thúc → webhook 404, trạng thái giao/COD/hàng hoàn không được cập nhật.
        $this->app->make(Extensions::class)->guardDisable(ShippingCarrier::CARRIERS_TAG, function (object $carrier): ?string {
            if (! $carrier instanceof ShippingCarrier) {
                return null;
            }
            $open = Shipment::query()->where('carrier_code', $carrier->code())
                ->whereNotIn('status', [ShipmentStatus::Delivered, ShipmentStatus::Returned, ShipmentStatus::Cancelled])->count();

            return $open === 0 ? null : "còn {$open} vận đơn chưa kết thúc của hãng {$carrier->code()}";
        });

        RateLimiter::for('shipping-webhooks', fn (Request $request): Limit => Limit::perMinute(600)->by((string) $request->ip()));

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('vani:orders:complete-delivered')->hourly()->withoutOverlapping()->onOneServer();
        });

        if ($this->app->runningInConsole()) {
            $this->commands([CompleteDeliveredOrdersCommand::class]);
        }

        if (! $this->app->routesAreCached()) {
            Route::middleware(['api', 'throttle:shipping-webhooks'])->prefix('api/shipping')->name('api.shipping.')->group($this->modulePath('Http/routes/webhooks.php'));
        }

        $this->loadAdminRoutes($this->modulePath('Http/routes/admin-home.php'));
        $this->loadAdminSectionRoutes('fulfillment', $this->modulePath('Http/routes/admin-workspace.php'));
        $this->bootModuleResources();
    }
}
