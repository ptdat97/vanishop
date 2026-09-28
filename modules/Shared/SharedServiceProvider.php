<?php

declare(strict_types=1);

namespace Modules\Shared;

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
        $this->bootModuleResources();
    }
}
