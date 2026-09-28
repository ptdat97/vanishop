<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Plugins;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Nạp ServiceProvider của plugin đã cài (theo file cache), cô lập lỗi từng plugin.
 */
final class PluginLoader
{
    /** @var array<string, string> plugin id => lỗi khi nạp */
    private array $failures = [];

    /** @var list<string> */
    private array $loaded = [];

    public function __construct(
        private readonly Application $app,
        private readonly PluginStateCache $cache,
        private readonly bool $safeMode,
    ) {}

    public function load(): void
    {
        if ($this->safeMode) {
            Log::warning('VaniShop plugin safe mode đang bật: không nạp plugin nào.');

            return;
        }

        foreach ($this->cache->read() as $plugin) {
            try {
                if (! class_exists($plugin['provider'])) {
                    throw new \RuntimeException("Không tìm thấy class provider [{$plugin['provider']}].");
                }

                $this->app->register($plugin['provider']);
                $this->loaded[] = $plugin['id'];
            } catch (Throwable $exception) {
                $this->failures[$plugin['id']] = $exception->getMessage();
                Log::error('Không nạp được plugin.', ['plugin' => $plugin['id'], 'exception' => $exception]);
            }
        }
    }

    /**
     * @return array<string, string>
     */
    public function failures(): array
    {
        return $this->failures;
    }

    /**
     * @return list<string>
     */
    public function loaded(): array
    {
        return $this->loaded;
    }
}
