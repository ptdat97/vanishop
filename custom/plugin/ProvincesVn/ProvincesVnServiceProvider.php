<?php

declare(strict_types=1);

namespace Plugin\ProvincesVn;

use Modules\Checkout\Contracts\AddressDirectory;
use Modules\Extension\Contracts\PluginHealthCheck;
use Modules\Extension\PluginServiceProvider;
use Plugin\ProvincesVn\Infrastructure\DivisionsHealthCheck;
use Plugin\ProvincesVn\Infrastructure\VnDivisions;

/**
 * Plugin hệ thống: danh mục địa giới hành chính Việt Nam (AddressDirectory) — checkout chọn tỉnh/phường, Core kiểm
 * tra mã và chụp tên chuẩn vào đơn.
 */
final class ProvincesVnServiceProvider extends PluginServiceProvider
{
    public const ID = 'vani.provinces-vn';

    protected function pluginId(): string
    {
        return self::ID;
    }

    public function register(): void
    {
        $this->app->singleton(VnDivisions::class, fn (): VnDivisions => new VnDivisions($this->pluginPath('Database/data/vn-divisions-2025.json')));
    }

    public function boot(): void
    {
        $this->contribute(AddressDirectory::TAG, VnDivisions::class);
        $this->contribute(PluginHealthCheck::TAG, DivisionsHealthCheck::class);
    }
}
