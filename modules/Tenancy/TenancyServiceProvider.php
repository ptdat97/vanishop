<?php

declare(strict_types=1);

namespace Modules\Tenancy;

use Modules\Shared\Support\ModuleServiceProvider;

final class TenancyServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Tenancy';
    }

    public function boot(): void
    {
        $this->bootModuleResources();
    }
}
