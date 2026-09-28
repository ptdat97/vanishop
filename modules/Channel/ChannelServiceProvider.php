<?php

declare(strict_types=1);

namespace Modules\Channel;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Channel\Application\DomainChannelResolver;
use Modules\Channel\Application\EloquentChannelDirectory;
use Modules\Channel\Contracts\ChannelDirectory;
use Modules\Channel\Contracts\ChannelResolver;
use Modules\Channel\Http\Middleware\ResolveApiChannel;
use Modules\Channel\Http\Middleware\ResolveChannel;
use Modules\Shared\Support\ModuleServiceProvider;

final class ChannelServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Channel';
    }

    public function register(): void
    {
        $this->app->singleton(ChannelResolver::class, DomainChannelResolver::class);
        $this->app->bind(ChannelDirectory::class, EloquentChannelDirectory::class);
    }

    public function boot(Router $router): void
    {
        $router->aliasMiddleware('vani.channel', ResolveChannel::class);
        $router->aliasMiddleware('vani.api-channel', ResolveApiChannel::class);

        RateLimiter::for('storefront-api', fn (Request $request): Limit => Limit::perMinute(240)->by((string) $request->ip()));
        $this->bootModuleResources();
    }
}
