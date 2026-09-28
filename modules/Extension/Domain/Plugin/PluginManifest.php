<?php

declare(strict_types=1);

namespace Modules\Extension\Domain\Plugin;

/**
 * Nội dung vanishop.json của một plugin.
 *
 * @see docs/05-plugin/plugin-system.md §3
 */
final readonly class PluginManifest
{
    private const ID_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*\.[a-z0-9]+(?:-[a-z0-9]+)*$/';

    private const VERSION_PATTERN = '/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/';

    /**
     * @param  array<string, string>  $name  locale => tên hiển thị
     * @param  array<string, string>  $requiresPlugins  plugin id => ràng buộc phiên bản
     * @param  list<string>  $conflicts
     * @param  list<string>  $scopes
     * @param  list<string>  $permissions
     */
    public function __construct(
        public string $id,
        public array $name,
        public string $version,
        public string $kind,
        public string $provider,
        public string $requiresCore,
        public array $requiresPlugins,
        public array $conflicts,
        public array $scopes,
        public array $permissions,
        public string $path,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, string $path): self
    {
        foreach (['id', 'name', 'version', 'kind', 'provider', 'requires'] as $field) {
            if (! array_key_exists($field, $data)) {
                throw InvalidManifest::because($path, "thiếu trường [{$field}]");
            }
        }

        $id = (string) $data['id'];
        if (preg_match(self::ID_PATTERN, $id) !== 1) {
            throw InvalidManifest::because($path, "id [{$id}] phải có dạng vendor.name (chữ thường, số, gạch ngang)");
        }

        $version = (string) $data['version'];
        if (preg_match(self::VERSION_PATTERN, $version) !== 1) {
            throw InvalidManifest::because($path, "version [{$version}] không phải SemVer");
        }

        $requires = is_array($data['requires']) ? $data['requires'] : [];
        if (! isset($requires['vanishop']) || ! is_string($requires['vanishop'])) {
            throw InvalidManifest::because($path, 'thiếu requires.vanishop');
        }

        $name = is_array($data['name']) ? $data['name'] : ['vi' => (string) $data['name']];

        return new self(
            id: $id,
            name: array_map('strval', $name),
            version: $version,
            kind: (string) $data['kind'],
            provider: (string) $data['provider'],
            requiresCore: $requires['vanishop'],
            requiresPlugins: array_map('strval', (array) ($requires['plugins'] ?? [])),
            conflicts: array_values(array_map('strval', (array) ($data['conflicts'] ?? []))),
            scopes: array_values(array_map('strval', (array) ($data['scopes'] ?? ['owner']))),
            permissions: array_values(array_map('strval', (array) ($data['permissions'] ?? []))),
            path: $path,
        );
    }

    public function displayName(string $locale = 'vi'): string
    {
        return $this->name[$locale] ?? $this->name['vi'] ?? $this->name['en'] ?? $this->id;
    }
}
