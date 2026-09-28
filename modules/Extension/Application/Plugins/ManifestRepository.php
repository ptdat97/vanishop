<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Plugins;

use Illuminate\Support\Facades\Log;
use JsonException;
use Modules\Extension\Domain\Plugin\InvalidManifest;
use Modules\Extension\Domain\Plugin\PluginManifest;

/**
 * Quét custom/plugin/*\/vanishop.json.
 */
final class ManifestRepository
{
    /** @var array<string, PluginManifest>|null */
    private ?array $manifests = null;

    /** @var array<string, string> path => lỗi */
    private array $invalid = [];

    public function __construct(private readonly string $pluginsPath) {}

    /**
     * @return array<string, PluginManifest>
     */
    public function all(): array
    {
        if ($this->manifests !== null) {
            return $this->manifests;
        }

        $this->manifests = [];
        foreach (glob($this->pluginsPath.'/*/vanishop.json') ?: [] as $file) {
            try {
                $data = json_decode((string) file_get_contents($file), true, 32, JSON_THROW_ON_ERROR);
                $manifest = PluginManifest::fromArray(is_array($data) ? $data : [], dirname($file));

                if (isset($this->manifests[$manifest->id])) {
                    throw InvalidManifest::because($file, "trùng id [{$manifest->id}]");
                }

                $this->manifests[$manifest->id] = $manifest;
            } catch (InvalidManifest|JsonException $exception) {
                $this->invalid[$file] = $exception->getMessage();
                Log::warning('Bỏ qua plugin có manifest lỗi.', ['file' => $file, 'error' => $exception->getMessage()]);
            }
        }

        ksort($this->manifests);

        return $this->manifests;
    }

    public function find(string $id): ?PluginManifest
    {
        return $this->all()[$id] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function invalid(): array
    {
        $this->all();

        return $this->invalid;
    }
}
