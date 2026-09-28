<?php

declare(strict_types=1);

namespace Modules\Brand;

use Illuminate\Routing\Router;
use Modules\Brand\Application\EloquentBrandDirectory;
use Modules\Brand\Contracts\BrandDirectory;
use Modules\Brand\Http\Middleware\ResolveAdminBrand;
use Modules\Shared\Support\ModuleServiceProvider;

final class BrandServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Brand';
    }

    public function register(): void
    {
        $this->app->singleton(BrandDirectory::class, EloquentBrandDirectory::class);
    }

    public function boot(Router $router): void
    {
        $router->aliasMiddleware('vani.admin-brand', ResolveAdminBrand::class);
        $this->bootModuleResources();
    }
}
