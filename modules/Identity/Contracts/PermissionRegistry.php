<?php

declare(strict_types=1);

namespace Modules\Identity\Contracts;

/**
 * Service contract: danh mục mã quyền do module/plugin đăng ký lúc boot (vai trò chọn trong danh mục này) — C1.
 */
interface PermissionRegistry
{
    public const WILDCARD = '*';

    public function register(string $code, string $description): void;

    public function has(string $code): bool;

    /**
     * @return array<string, string>
     */
    public function all(): array;
}
