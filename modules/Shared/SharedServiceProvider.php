<?php

declare(strict_types=1);

namespace Modules\Shared;

use Illuminate\Console\Scheduling\Schedule;
use Modules\Shared\Console\PruneIdempotencyKeysCommand;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Support\ModuleServiceProvider;

final class SharedServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Shared';
    }

    public function register(): void
    {
        $this->app->scoped(CurrentContext::class);
    }

    public function boot(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('vani:idempotency:prune')->hourly()->withoutOverlapping()->onOneServer();
        });

        if ($this->app->runningInConsole()) {
            $this->commands([PruneIdempotencyKeysCommand::class]);
        }

        $this->bootModuleResources();
    }
}
