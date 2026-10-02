<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Hooks;

use Modules\Extension\Application\Plugins\ManifestRepository;

/**
 * Xác định plugin sở hữu listener từ vị trí gọi (file nằm trong thư mục plugin nào) — để helper ngắn
 * `vani_add_filter()` vẫn gắn listener với plugin (chỉ chạy khi plugin bật) mà không cần truyền plugin id.
 * Gọi từ modules/ (Core) → null.
 */
final class CallerPlugin
{
    /** @var array<string, string>|null thư mục plugin (realpath) => plugin id */
    private ?array $paths = null;

    public function resolve(): ?string
    {
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 12) as $frame) {
            $file = $frame['file'] ?? null;
            if ($file === null || str_ends_with($file, '/Extension/helpers.php') || str_contains($file, '/Extension/Application/Hooks/')) {
                continue;
            }

            foreach ($this->paths() as $path => $plugin) {
                if (str_starts_with($file, $path.DIRECTORY_SEPARATOR)) {
                    return $plugin;
                }
            }

            return null;
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function paths(): array
    {
        if ($this->paths === null) {
            $this->paths = [];
            foreach (app(ManifestRepository::class)->all() as $manifest) {
                $this->paths[realpath($manifest->path) ?: $manifest->path] = $manifest->id;
            }
        }

        return $this->paths;
    }
}
