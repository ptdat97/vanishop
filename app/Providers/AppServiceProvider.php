<?php

namespace App\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;
use Modules\Identity\Application\PermissionRegistry;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(PermissionRegistry $permissions): void
    {
        // Dashboard vận hành (Horizon, Pulse — ADR-032): chỉ nhân viên có quyền system.monitor.
        $permissions->register('system.monitor', 'Xem dashboard vận hành (queue, hiệu năng)');
        Gate::define('viewPulse', fn ($user = null): bool => $user !== null && Gate::forUser($user)->allows('system.monitor'));
        // Backup DB + file dữ liệu hằng đêm, dọn bản cũ, kiểm tra sức khoẻ backup (go-live gate).
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('backup:clean')->dailyAt('01:30')->onOneServer();
            $schedule->command('backup:run')->dailyAt('02:00')->withoutOverlapping()->onOneServer();
            $schedule->command('backup:monitor')->dailyAt('09:00')->onOneServer();
            $schedule->command('horizon:snapshot')->everyFiveMinutes()->onOneServer();
        });
        Horizon::auth(fn (Request $request): bool => ($user = $request->user('staff')) !== null && Gate::forUser($user)->allows('system.monitor'));
    }
}
