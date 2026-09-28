<?php

declare(strict_types=1);

namespace Modules\Identity\Application;

/**
 * Danh sách permission do module Core và plugin khai báo.
 */
final class PermissionRegistry
{
    public const WILDCARD = '*';

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

        return $this->permissions;
    }
}
