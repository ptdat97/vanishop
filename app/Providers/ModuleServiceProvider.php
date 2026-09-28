<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Đăng ký ServiceProvider của từng module Core theo thứ tự trong config/modules.php.
 */
final class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        /** @var list<string> $modules */
        $modules = config('modules', []);

        foreach ($modules as $module) {
            $this->app->register("Modules\\{$module}\\{$module}ServiceProvider");
        }
    }
}
