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
use Modules\Extension\Contracts\Requirement;
use Modules\Extension\Facades\Hook;
use Modules\Fulfillment\Events\ShipmentStatusChanged;
use Modules\Identity\Application\PermissionRegistry;
use Modules\Ordering\Events\OrderCancelled;
use Modules\Ordering\Events\OrderLinesCancelled;
use Modules\Payment\Application\Listeners\CaptureAuthorizedOnShipment;
use Modules\Payment\Application\Listeners\CollectCodOnDelivery;
use Modules\Payment\Application\Listeners\OrderPaymentPanel;
use Modules\Payment\Application\Listeners\SettleCancelledOrderPayments;
use Modules\Payment\Application\Listeners\SettlePartialCancellation;
use Modules\Payment\Application\PaymentService;
use Modules\Payment\Console\ExpirePaymentsCommand;
use Modules\Payment\Console\ReconcilePaymentsCommand;
use Modules\Payment\Contracts\PaymentGateway;
use Modules\Payment\Contracts\Payments;
use Modules\Payment\Domain\PaymentStatus;
use Modules\Payment\Persistence\Models\Payment;
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
        // Cổng thanh toán đều là plugin (COD, chuyển khoản: plugin hệ thống vani.cod, vani.bank-transfer — ADR-029).
        $this->app->make(Extensions::class)->requires(PaymentGateway::TAG, Requirement::AtLeastOne, 'Cổng thanh toán');
        $this->app->make(Extensions::class)->kindContract('payment_gateway', PaymentGateway::TAG);
    }

    public function boot(PermissionRegistry $permissions, AdminNavigation $navigation): void
    {
        $permissions->register('payments.view', 'Xem thanh toán');
        $permissions->register('payments.confirm', 'Xác nhận đã nhận tiền (chuyển khoản thủ công)');
        $permissions->register('payments.refund', 'Hoàn tiền');

        $navigation->add('payment', 'Thanh toán', 'admin.payment.home', 'payments.view', 350);

        Event::listen(OrderCancelled::class, SettleCancelledOrderPayments::class);
        Event::listen(OrderLinesCancelled::class, SettlePartialCancellation::class);
        Event::listen(ShipmentStatusChanged::class, CollectCodOnDelivery::class);
        Event::listen(ShipmentStatusChanged::class, CaptureAuthorizedOnShipment::class);
        Hook::onSlot('vani.admin.order.sidebar', fn ($order) => $this->app->make(OrderPaymentPanel::class)($order));

        // Tắt cổng còn khoản chờ/giữ tiền → IPN 404, tiền khách đã trả không được ghi nhận.
        $this->app->make(Extensions::class)->guardDisable(PaymentGateway::TAG, function (object $gateway): ?string {
            if (! $gateway instanceof PaymentGateway) {
                return null;
            }
            $open = Payment::query()->where('gateway_code', $gateway->code())->whereIn('status', [PaymentStatus::Pending, PaymentStatus::Authorized])->count();

            return $open === 0 ? null : "còn {$open} khoản thanh toán chờ/giữ tiền qua cổng {$gateway->code()}";
        });

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
