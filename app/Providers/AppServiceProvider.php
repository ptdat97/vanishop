<?php

namespace App\Providers;

use App\Livewire\Pulse\CommerceMetricsCard;
use App\Observability\MailAlertChannel;
use App\Observability\OpenAlertsWidget;
use App\Observability\PulseMetrics;
use App\Observability\RecordCommerceMetrics;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;
use Livewire\Livewire;
use Modules\Extension\Contracts\DashboardWidget;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Contracts\Requirement;
use Modules\Identity\Application\PermissionRegistry;
use Modules\Shared\Application\Phone\InternationalPhonePolicy;
use Modules\Shared\Contracts\AlertChannel;
use Modules\Shared\Contracts\Metrics;
use Modules\Shared\Contracts\PhoneNumberPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Metrics::class, PulseMetrics::class);
        // Luật số điện thoại của thị trường (0.3.42): implementation đang bật theo cấu hình; không có hiệu lực → `international`.
        $this->app->bind(PhoneNumberPolicy::class, fn ($app): PhoneNumberPolicy => $app->make(Extensions::class)->select(
            PhoneNumberPolicy::TAG, (string) config('vanishop.locale.phone_policy', InternationalPhonePolicy::CODE), InternationalPhonePolicy::CODE,
        ) ?? new InternationalPhonePolicy);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(PermissionRegistry $permissions): void
    {
        self::trustProxies((string) config('vanishop.trusted_proxies'));
        // Dashboard vận hành (Horizon, Pulse — ADR-032): chỉ nhân viên có quyền system.monitor.
        $permissions->register('system.monitor', 'Xem dashboard vận hành (queue, hiệu năng)');
        Gate::define('viewPulse', fn ($user = null): bool => $user !== null && Gate::forUser($user)->allows('system.monitor'));
        // Backup DB + file dữ liệu hằng đêm, dọn bản cũ, kiểm tra sức khoẻ backup (go-live gate).
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('backup:clean')->dailyAt('01:30')->onOneServer();
            $schedule->command('backup:run')->dailyAt('02:00')->withoutOverlapping()->onOneServer();
            $schedule->command('backup:monitor')->dailyAt('09:00')->onOneServer();
            $schedule->command('horizon:snapshot')->everyFiveMinutes()->onOneServer();
            $schedule->command('vani:metrics:snapshot')->everyMinute()->withoutOverlapping()->onOneServer();
            $schedule->command('vani:alerts:check')->everyMinute()->withoutOverlapping()->onOneServer();
        });
        // Cảnh báo tự động: kênh email của Core; plugin thêm kênh qua AlertChannel::TAG.
        $this->app->make(Extensions::class)->tag([MailAlertChannel::class], AlertChannel::TAG);
        $this->app->make(Extensions::class)->tag([OpenAlertsWidget::class], DashboardWidget::TAG);
        $this->app->make(Extensions::class)->tag([InternationalPhonePolicy::class], PhoneNumberPolicy::TAG);
        $this->app->make(Extensions::class)->requires(PhoneNumberPolicy::TAG, Requirement::ExactlyOne, 'Định dạng số điện thoại');
        // Counter thương mại từ domain event (sau commit) — Phase 6 observability.
        foreach (RecordCommerceMetrics::listeners() as $event => $method) {
            Event::listen($event, [RecordCommerceMetrics::class, $method]);
        }
        Livewire::component('vani.commerce-metrics', CommerceMetricsCard::class);
        Horizon::auth(fn (Request $request): bool => ($user = $request->user('staff')) !== null && Gate::forUser($user)->allows('system.monitor'));
    }

    /**
     * Sau LB/CDN: IP khách (allowlist Admin, giới hạn OTP) và HTTPS lấy từ X-Forwarded-* của proxy tin cậy
     * (`VANI_TRUSTED_PROXIES`: IP/CIDR phân tách dấu phẩy, hoặc '*'). Trống = không tin header nào.
     */
    public static function trustProxies(string $setting): void
    {
        $setting = trim($setting);
        if ($setting !== '') {
            TrustProxies::at($setting === '*' ? '*' : array_values(array_filter(array_map('trim', explode(',', $setting)))));
        }
    }
}
