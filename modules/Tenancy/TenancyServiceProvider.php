<?php

declare(strict_types=1);

namespace Modules\Tenancy;

use Modules\Shared\Support\ModuleServiceProvider;
use Modules\Tenancy\Application\DatabaseSettings;
use Modules\Tenancy\Application\EloquentLegalEntityDirectory;
use Modules\Tenancy\Application\SettingDefinitions;
use Modules\Tenancy\Contracts\LegalEntityDirectory;
use Modules\Tenancy\Contracts\Settings;

final class TenancyServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Tenancy';
    }

    public function register(): void
    {
        $this->app->bind(LegalEntityDirectory::class, EloquentLegalEntityDirectory::class);
        // Định nghĩa sống suốt tiến trình; giá trị nạp một lần mỗi request/job (scoped).
        $this->app->singleton(SettingDefinitions::class);
        $this->app->scoped(Settings::class, DatabaseSettings::class);
    }

    public function boot(): void
    {
        $this->bootModuleResources();
    }
}
