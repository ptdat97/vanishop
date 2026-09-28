<?php

declare(strict_types=1);

namespace Modules\Storefront;

use Modules\Shared\Support\ModuleServiceProvider;

/**
 * Tầng ghép cho storefront (Storefront API và native storefront): không có bảng riêng,
 * chỉ gọi contract của các module Core. Xem docs/14-storefront/storefront.md.
 */
final class StorefrontServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Storefront';
    }

    public function boot(): void
    {
        $this->loadStorefrontApiRoutes($this->modulePath('Http/routes/storefront-api.php'));
        $this->bootModuleResources();
    }
}
