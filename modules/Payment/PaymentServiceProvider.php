<?php

declare(strict_types=1);

namespace Modules\Payment;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Modules\Extension\Application\Admin\AdminNavigation;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Facades\Hook;
use Modules\Fulfillment\Events\ShipmentStatusChanged;
use Modules\Identity\Application\PermissionRegistry;
use Modules\Ordering\Events\OrderCancelled;
use Modules\Ordering\Events\OrderPlaced;
use Modules\Payment\Application\GatewayRegistry;
use Modules\Payment\Application\Gateways\CodGateway;
use Modules\Payment\Application\Gateways\ManualBankTransferGateway;
use Modules\Payment\Application\Listeners\AutoConfirmCodOrder;
use Modules\Payment\Application\Listeners\CollectCodOnDelivery;
use Modules\Payment\Application\Listeners\OrderPaymentPanel;
use Modules\Payment\Application\Listeners\SettleCancelledOrderPayments;
use Modules\Payment\Application\PaymentService;
use Modules\Payment\Console\ExpirePaymentsCommand;
use Modules\Payment\Console\ReconcilePaymentsCommand;
use Modules\Payment\Contracts\Payments;
use Modules\Shared\Support\ModuleServiceProvider;

final class PaymentServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Payment';
    }

    public function register(): void
    {
        $this->app->bind(Payments::class, PaymentService::class);

        $this->app->bind(CodGateway::class, fn (): CodGateway => new CodGateway(
            config('vanishop.payment.cod.max_amount') === null ? null : (int) config('vanishop.payment.cod.max_amount'),
        ));
        $this->app->bind(ManualBankTransferGateway::class, fn (): ManualBankTransferGateway => new ManualBankTransferGateway(
            (array) config('vanishop.payment.bank_transfer.account', []),
            (int) config('vanishop.payment.bank_transfer.ttl', 86_400),
        ));
        $this->app->make(Extensions::class)->tag([CodGateway::class, ManualBankTransferGateway::class], GatewayRegistry::TAG);
    }

    public function boot(PermissionRegistry $permissions, AdminNavigation $navigation): void
    {
        $permissions->register('payments.view', 'Xem thanh toán');
        $permissions->register('payments.confirm', 'Xác nhận đã nhận tiền (chuyển khoản thủ công)');
        $permissions->register('payments.refund', 'Hoàn tiền');

        $navigation->add('payment', 'Thanh toán', 'admin.payment.home', 'payments.view', 350);

        Event::listen(OrderPlaced::class, AutoConfirmCodOrder::class);
        Event::listen(OrderCancelled::class, SettleCancelledOrderPayments::class);
        Event::listen(ShipmentStatusChanged::class, CollectCodOnDelivery::class);
        Hook::onSlot('vani.admin.order.sidebar', fn ($order) => $this->app->make(OrderPaymentPanel::class)($order));

        RateLimiter::for('payment-callbacks', fn (Request $request): Limit => Limit::perMinute(600)->by((string) $request->ip()));

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('vani:payment:expire')->everyMinute()->withoutOverlapping()->onOneServer();
            $schedule->command('vani:payment:reconcile')->everyMinute()->withoutOverlapping()->onOneServer();
        });

        if ($this->app->runningInConsole()) {
            $this->commands([ExpirePaymentsCommand::class, ReconcilePaymentsCommand::class]);
        }

        if (! $this->app->routesAreCached()) {
            Route::middleware(['api', 'throttle:payment-callbacks'])->prefix('api/payments')->name('api.payments.')->group($this->modulePath('Http/routes/callbacks.php'));
        }

        $this->loadAdminRoutes($this->modulePath('Http/routes/admin-home.php'));
        $this->loadAdminSectionRoutes('payment', $this->modulePath('Http/routes/admin-workspace.php'));
        $this->bootModuleResources();
    }
}
