<?php

declare(strict_types=1);

namespace Modules\Customer;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Customer\Application\AuthService;
use Modules\Customer\Application\CustomerAccountService;
use Modules\Customer\Application\CustomerSegmentService;
use Modules\Customer\Application\CustomerService;
use Modules\Customer\Application\CustomerSessionService;
use Modules\Customer\Application\Listeners\RefreshCustomerStats;
use Modules\Customer\Application\OtpSenders\EmailOtpSender;
use Modules\Customer\Application\OtpSenders\LogOtpSender;
use Modules\Customer\Application\OtpService;
use Modules\Customer\Contracts\CustomerAccounts;
use Modules\Customer\Contracts\Customers;
use Modules\Customer\Contracts\CustomerSegments;
use Modules\Customer\Contracts\CustomerSessions;
use Modules\Customer\Contracts\OtpSender;
use Modules\Customer\Http\Middleware\AuthenticateCustomer;
use Modules\Customer\Http\Middleware\CustomerSession;
use Modules\Extension\Application\Admin\AdminNavigation;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Contracts\Requirement;
use Modules\Identity\Application\PermissionRegistry;
use Modules\Ordering\Events\OrderCancelled;
use Modules\Ordering\Events\OrderLinesCancelled;
use Modules\Ordering\Events\OrderPlaced;
use Modules\Pricing\Contracts\CustomerGroupDirectory;
use Modules\Shared\Http\RateLimits;
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
        // Phân khúc khách: một instance mỗi request (cache nhóm khách khi tính giá nhiều lần).
        $this->app->scoped(CustomerSegmentService::class);
        $this->app->alias(CustomerSegmentService::class, CustomerSegments::class);
        $this->app->bind(CustomerGroupDirectory::class, fn ($app): CustomerGroupDirectory => $app->make(CustomerSegmentService::class));
        $this->app->bind(CustomerSessions::class, CustomerSessionService::class);
        $this->app->bind(CustomerAccounts::class, CustomerAccountService::class);
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
        $this->app->make(Extensions::class)->requires(OtpSender::TAG, Requirement::AtLeastOne, 'Kênh gửi OTP');
    }

    public function boot(PermissionRegistry $permissions, AdminNavigation $navigation, Router $router): void
    {
        $permissions->register('customers.view', 'Xem khách hàng (cấp Owner)');
        $permissions->register('customers.merge', 'Hợp nhất khách hàng trùng');
        $permissions->register('customers.anonymize', 'Ẩn danh hoá khách hàng theo yêu cầu xoá');
        $permissions->register('customers.segment', 'Quản lý nhóm khách và gán nhóm/tag cho khách');

        $navigation->add('customers', 'Khách hàng', 'admin.customers.index', 'customers.view', 450);

        $router->aliasMiddleware('vani.customer', AuthenticateCustomer::class);
        $router->aliasMiddleware('vani.customer-session', CustomerSession::class);

        Event::listen(OrderPlaced::class, RefreshCustomerStats::class);
        Event::listen(OrderCancelled::class, RefreshCustomerStats::class);
        Event::listen(OrderLinesCancelled::class, RefreshCustomerStats::class);

        RateLimiter::for('vani-customer-auth', fn (Request $request): Limit => RateLimits::perMinuteByIp($request, 20, 'customer-auth:'));

        $this->loadStorefrontApiRoutes($this->modulePath('Http/routes/storefront-api.php'));
        $this->loadAdminRoutes($this->modulePath('Http/routes/admin.php'));
        $this->bootModuleResources();
    }
}
