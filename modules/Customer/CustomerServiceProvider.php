<?php

declare(strict_types=1);

namespace Modules\Customer;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Customer\Application\AccountEmails;
use Modules\Customer\Application\AuthService;
use Modules\Customer\Application\ConsentService;
use Modules\Customer\Application\CustomerAccountService;
use Modules\Customer\Application\CustomerNoteService;
use Modules\Customer\Application\CustomerSegmentService;
use Modules\Customer\Application\CustomerService;
use Modules\Customer\Application\CustomerSessionService;
use Modules\Customer\Application\Listeners\OrderCustomerNotesPanel;
use Modules\Customer\Application\Listeners\RefreshCustomerStats;
use Modules\Customer\Application\Listeners\RememberPreferredLocale;
use Modules\Customer\Application\OtpSenders\EmailOtpSender;
use Modules\Customer\Application\OtpSenders\LogOtpSender;
use Modules\Customer\Application\OtpService;
use Modules\Customer\Application\PhoneOtpLoginService;
use Modules\Customer\Contracts\CustomerAccounts;
use Modules\Customer\Contracts\CustomerNotes;
use Modules\Customer\Contracts\Customers;
use Modules\Customer\Contracts\CustomerSegments;
use Modules\Customer\Contracts\CustomerSessions;
use Modules\Customer\Contracts\OtpSender;
use Modules\Customer\Contracts\PhoneOtpLogin;
use Modules\Customer\Http\Middleware\AuthenticateCustomer;
use Modules\Customer\Http\Middleware\CustomerSession;
use Modules\Extension\Contracts\AdminScreen;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Contracts\Requirement;
use Modules\Extension\Facades\Hook;
use Modules\Identity\Contracts\AdminNavigation;
use Modules\Identity\Contracts\PermissionRegistry;
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
        // Màn hình Admin của module mở cho plugin thêm form/cột/lọc/thao tác/tab (ADR-030).
        $this->app->make(AdminScreen::class)->declareResource('customer');
        // Phân khúc khách: một instance mỗi request (cache nhóm khách khi tính giá nhiều lần).
        $this->app->scoped(CustomerSegmentService::class);
        $this->app->alias(CustomerSegmentService::class, CustomerSegments::class);
        // EP v3 X6: ghi chú CSKH theo khách.
        $this->app->bind(CustomerNotes::class, CustomerNoteService::class);
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
            (int) config('vanishop.customer.otp.ttl', 300),
            (int) config('vanishop.customer.otp.max_attempts', 5),
        ));
        $this->app->bind(AuthService::class, fn ($app): AuthService => new AuthService(
            $app->make(OtpService::class), $app->make(CustomerService::class), $app->make(AccountEmails::class), $app->make(ConsentService::class),
            (int) config('vanishop.customer.token_ttl_days', 90),
            (int) config('vanishop.customer.email_auth.verify_ttl_hours', 24),
            (int) config('vanishop.customer.email_auth.reset_ttl_minutes', 60),
        ));
        // OTP theo SĐT: phương thức bổ sung do plugin `vani.phone-otp-login` bật (0.5.12).
        $this->app->bind(PhoneOtpLogin::class, PhoneOtpLoginService::class);
        $this->app->bind(LogOtpSender::class, fn (): LogOtpSender => new LogOtpSender((bool) config('vanishop.customer.otp.log_sender', false)));
        $this->app->make(Extensions::class)->tag([EmailOtpSender::class, LogOtpSender::class], OtpSender::TAG);
        $this->app->make(Extensions::class)->requires(OtpSender::TAG, Requirement::AtLeastOne, 'customer::texts.customer_service_provider.otp_delivery_channel');
    }

    public function boot(PermissionRegistry $permissions, AdminNavigation $navigation, Router $router): void
    {
        $permissions->register('customers.view', 'customer::texts.customer_service_provider.view_customers_owner_level', [PermissionRegistry::PRESET_ORDER_OPS, PermissionRegistry::PRESET_MARKETING, PermissionRegistry::PRESET_ANALYST]);
        $permissions->register('customers.merge', 'customer::texts.customer_service_provider.merge_duplicate_customers', [PermissionRegistry::PRESET_ORDER_OPS]);
        $permissions->register('customers.anonymize', 'customer::texts.customer_service_provider.anonymise_customers_deletion_requests');
        $permissions->register('customers.segment', 'customer::texts.customer_service_provider.manage_customer_groups_assign_groups', [PermissionRegistry::PRESET_MARKETING]);

        $navigation->add('customers', 'customer::texts.customer_service_provider.customers', 'admin.customers.index', 'customers.view', 110);

        $router->aliasMiddleware('vani.customer', AuthenticateCustomer::class);
        $router->aliasMiddleware('vani.customer-session', CustomerSession::class);

        Event::listen(OrderPlaced::class, RefreshCustomerStats::class);
        Event::listen(OrderPlaced::class, RememberPreferredLocale::class);
        Hook::onSlot('vani.admin.order.sidebar', fn ($order) => $this->app->make(OrderCustomerNotesPanel::class)($order), priority: 120);
        Event::listen(OrderCancelled::class, RefreshCustomerStats::class);
        Event::listen(OrderLinesCancelled::class, RefreshCustomerStats::class);

        RateLimiter::for('vani-customer-auth', fn (Request $request): Limit => RateLimits::perMinuteByIp($request, (int) config('vanishop.rate_limits.customer_auth', 20), 'customer-auth:'));

        $this->loadStorefrontApiRoutes($this->modulePath('Http/routes/storefront-api.php'));
        $this->loadAdminRoutes($this->modulePath('Http/routes/admin.php'));
        $this->bootModuleResources();
    }
}
