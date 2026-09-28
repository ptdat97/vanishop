<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Plugins;

use Illuminate\Filesystem\Filesystem;

/**
 * File cache trạng thái plugin để lúc boot không cần truy vấn DB.
 * Được ghi lại mỗi khi install/enable/disable/uninstall.
 *
 * @phpstan-type CachedPlugin array{id: string, provider: string, path: string, status: string}
 */
final class PluginStateCache
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly string $path,
    ) {}

    /**
     * @return list<CachedPlugin> theo thứ tự nạp
     */
    public function read(): array
    {
        if (! $this->files->exists($this->path)) {
            return [];
        }

        $data = require $this->path;

        return is_array($data) ? $data : [];
    }

    /**
     * @param  list<CachedPlugin>  $plugins
     */
    public function write(array $plugins): void
    {
        $this->files->ensureDirectoryExists(dirname($this->path));
        $this->files->put($this->path, '<?php return '.var_export($plugins, true).';'.PHP_EOL, true);
    }

    public function forget(): void
    {
        $this->files->delete($this->path);
    }
}
