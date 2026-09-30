<?php

declare(strict_types=1);

namespace Modules\Customer;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Customer\Application\AuthService;
use Modules\Customer\Application\CustomerService;
use Modules\Customer\Application\Listeners\RefreshBrandProfile;
use Modules\Customer\Application\OtpSenders\EmailOtpSender;
use Modules\Customer\Application\OtpSenders\LogOtpSender;
use Modules\Customer\Application\OtpService;
use Modules\Customer\Contracts\Customers;
use Modules\Customer\Contracts\OtpSender;
use Modules\Customer\Http\Middleware\AuthenticateCustomer;
use Modules\Extension\Application\Admin\AdminNavigation;
use Modules\Extension\Contracts\Extensions;
use Modules\Identity\Application\PermissionRegistry;
use Modules\Ordering\Events\OrderCancelled;
use Modules\Ordering\Events\OrderPlaced;
use Modules\Shared\Support\ModuleServiceProvider;

/**
 * Khách hàng hợp nhất toàn Owner ("Vani ID"): hồ sơ, xác thực OTP/mật khẩu, sổ địa chỉ, consent, merge, ẩn danh hoá.
 *
 * @see docs/03-domains/customer.md
 */
final class CustomerServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Customer';
    }

    public function register(): void
    {
        $this->app->bind(Customers::class, CustomerService::class);
        $this->app->bind(OtpService::class, fn ($app): OtpService => new OtpService(
            $app->make(Extensions::class),
            $app->make(CustomerService::class),
            (string) config('app.key'),
            (int) config('vanishop.customer.otp.per_phone', 3),
            (int) config('vanishop.customer.otp.per_ip', 10),
            (int) config('vanishop.customer.otp.window', 600),
        ));
        $this->app->bind(AuthService::class, fn ($app): AuthService => new AuthService(
            $app->make(OtpService::class), $app->make(CustomerService::class), (int) config('vanishop.customer.token_ttl_days', 90),
        ));
        $this->app->bind(LogOtpSender::class, fn (): LogOtpSender => new LogOtpSender((bool) config('vanishop.customer.otp.log_sender', false)));
        $this->app->make(Extensions::class)->tag([EmailOtpSender::class, LogOtpSender::class], OtpSender::TAG);
    }

    public function boot(PermissionRegistry $permissions, AdminNavigation $navigation, Router $router): void
    {
        $permissions->register('customers.view', 'Xem khách hàng (cấp Owner)');
        $permissions->register('customers.merge', 'Hợp nhất khách hàng trùng');
        $permissions->register('customers.anonymize', 'Ẩn danh hoá khách hàng theo yêu cầu xoá');

        $navigation->add('customers', 'Khách hàng', 'admin.customers.index', 'customers.view', 450);

        $router->aliasMiddleware('vani.customer', AuthenticateCustomer::class);

        Event::listen(OrderPlaced::class, RefreshBrandProfile::class);
        Event::listen(OrderCancelled::class, RefreshBrandProfile::class);

        RateLimiter::for('vani-customer-auth', fn (Request $request): Limit => Limit::perMinute(20)->by('customer-auth:'.$request->ip()));

        $this->loadStorefrontApiRoutes($this->modulePath('Http/routes/storefront-api.php'));
        $this->loadAdminRoutes($this->modulePath('Http/routes/admin.php'));
        $this->bootModuleResources();
    }
}
