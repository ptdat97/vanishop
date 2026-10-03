<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Modules\Extension\Application\Plugins\ManifestRepository;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Domain\Plugin\PluginStatus;
use Modules\Extension\Persistence\Models\PluginRecord;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $traits = class_uses_recursive($this);
        if (in_array(RefreshDatabase::class, $traits, true)) {
            $this->enableBundledPlugins();
        } elseif (in_array(DatabaseTruncation::class, $traits, true)) {
            // Concurrency test: tiến trình con boot app mới → cần bản ghi plugin + file cache nạp provider.
            $this->enableBundledPlugins();
            $this->app->make(PluginManager::class)->rebuildCache();
        }
    }

    /**
     * `migrate:fresh` đầu phiên test chạy cả migration của mọi plugin trong custom/plugin: khi test cài plugin, bảng
     * đã có nên không chạy DDL giữa transaction của test (MySQL tự commit khi gặp DDL → mất savepoint của RefreshDatabase).
     */
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $migrator = $app->make('migrator');
        foreach ($app->make(ManifestRepository::class)->all() as $manifest) {
            if (is_dir($manifest->path.'/Database/migrations')) {
                $migrator->path($manifest->path.'/Database/migrations');
            }
        }

        return $app;
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
