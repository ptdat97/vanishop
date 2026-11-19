<?php

declare(strict_types=1);

namespace Modules\Identity\Application;

use Modules\Identity\Contracts\PermissionRegistry;

/**
 * Danh sách permission do module Core và plugin khai báo.
 */
final class InMemoryPermissionRegistry implements PermissionRegistry
{
    /** @var array<string, array{description: string, presets: list<string>, params: array<string, string>}> */
    private array $permissions = [];

    public function register(string $code, string $description, array $presets = [], array $params = []): void
    {
        // Đăng ký lại cùng mã (vd. plugin gọi settings() nhiều lần): giữ mẫu đã khai, thêm mẫu mới.
        $presets = [...($this->permissions[$code]['presets'] ?? []), ...$presets];
        $this->permissions[$code] = ['description' => $description, 'presets' => array_values(array_unique($presets)), 'params' => $params];
    }

    public function has(string $code): bool
    {
        return isset($this->permissions[$code]);
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        ksort($this->permissions);

        // Mô tả là khoá dịch (đăng ký lúc boot, ADR-049) — dịch theo ngôn ngữ hiện tại.
        return array_map(fn (array $permission): string => (string) __($permission['description'], $permission['params']), $this->permissions);
    }

    public function preset(string $preset): array
    {
        ksort($this->permissions);
        $codes = array_keys(array_filter($this->permissions, fn (array $permission): bool => in_array($preset, $permission['presets'], true)));

        return array_values(array_unique(['admin.access', ...array_map('strval', $codes)]));
    }
}
