<?php

declare(strict_types=1);

namespace Modules\Identity\Application;

use Modules\Identity\Contracts\PermissionRegistry;

/**
 * Danh sách permission do module Core và plugin khai báo.
 */
final class InMemoryPermissionRegistry implements PermissionRegistry
{
    /** @var array<string, string> */
    private array $permissions = [];

    public function register(string $code, string $description): void
    {
        $this->permissions[$code] = $description;
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
        return array_map(fn (string $description): string => __($description), $this->permissions);
    }
}
