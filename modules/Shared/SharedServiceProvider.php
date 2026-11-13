<?php

declare(strict_types=1);

namespace Modules\Shared;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Factory as ValidationFactory;
use Modules\Shared\Console\PruneIdempotencyKeysCommand;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Http\LocalizedValidator;
use Modules\Shared\Http\Middleware\FirstPartySession;
use Modules\Shared\Http\Middleware\ResolveStorefrontContext;
use Modules\Shared\Http\RateLimits;
use Modules\Shared\Support\ModuleServiceProvider;
use Modules\Shared\Support\MoneyFormatter;

final class SharedServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Shared';
    }

    public function register(): void
    {
        $this->app->scoped(CurrentContext::class);
        $this->app->singleton(MoneyFormatter::class, fn (): MoneyFormatter => MoneyFormatter::fromConfig());
    }

    public function boot(Router $router): void
    {
        $router->aliasMiddleware('vani.storefront-context', ResolveStorefrontContext::class);
        $router->aliasMiddleware('vani.first-party-session', FirstPartySession::class);
        // Tên trường dễ đọc cho khoá lồng nhau (translations.vi.name, cells.0.sku) trong thông báo validation.
        $this->app->make(ValidationFactory::class)->resolver(
            fn ($translator, array $data, array $rules, array $messages, array $attributes): LocalizedValidator => new LocalizedValidator($translator, $data, $rules, $messages, $attributes),
        );
        RateLimiter::for('storefront-api', fn (Request $request): Limit => RateLimits::perMinuteByIp($request, (int) config('vanishop.rate_limits.storefront_api', 240)));

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('vani:idempotency:prune')->hourly()->withoutOverlapping()->onOneServer();
        });

        if ($this->app->runningInConsole()) {
            $this->commands([PruneIdempotencyKeysCommand::class]);
        }

        $this->bootModuleResources();
    }
}
