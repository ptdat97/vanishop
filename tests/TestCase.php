<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Modules\Extension\Application\Plugins\ManifestRepository;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Domain\Plugin\PluginStatus;
use Modules\Extension\Persistence\Models\PluginRecord;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (in_array(RefreshDatabase::class, class_uses_recursive($this), true)) {
            $this->enableBundledPlugins();
        }
    }

    /**
     * Như sau `vani:install`: plugin hệ thống (`"bundled": true` — COD, chuyển khoản, phí giao, VAT) đã cài + bật
     * (ADR-029). Ghi thẳng bản ghi + nạp provider cho nhanh, không qua PluginManager (không audit, không ghi cache file).
     */
    protected function enableBundledPlugins(): void
    {
        foreach ($this->app->make(ManifestRepository::class)->all() as $manifest) {
            if (! $manifest->bundled) {
                continue;
            }

            PluginRecord::query()->create([
                'id' => $manifest->id,
                'version' => $manifest->version,
                'status' => PluginStatus::Enabled,
                'installed_at' => now(),
            ]);
            $this->app->register($manifest->provider);
        }

        PluginActivation::forgetCache();
        $this->app->make(PluginActivation::class)->flush();
    }
}
