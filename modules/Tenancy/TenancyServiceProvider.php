<?php

declare(strict_types=1);

namespace Modules\Tenancy;

use Modules\Shared\Support\ModuleServiceProvider;
use Modules\Tenancy\Application\EloquentLegalEntityDirectory;
use Modules\Tenancy\Contracts\LegalEntityDirectory;

final class TenancyServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Tenancy';
    }

    public function register(): void
    {
        $this->app->bind(LegalEntityDirectory::class, EloquentLegalEntityDirectory::class);
    }

    public function boot(): void
    {
        $this->bootModuleResources();
    }
}
