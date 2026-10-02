<?php

declare(strict_types=1);

namespace Modules\Tenancy\Contracts;

use Modules\Tenancy\Contracts\Data\SettingDefinition;

/**
 * Service contract: cấu hình của cửa hàng (một cấp, ADR-028) cho Core và plugin.
 * `namespace` = `core` hoặc id plugin. Giá trị lưu dạng JSON; secret mã hoá bằng APP_KEY.
 */
interface Settings
{
    /**
     * Giá trị đã đặt; chưa đặt → default của định nghĩa → $default.
     */
    public function get(string $namespace, string $key, mixed $default = null): mixed;

    public function set(string $namespace, string $key, mixed $value): void;

    /** Xoá giá trị đã đặt (quay về mặc định). */
    public function forget(string $namespace, string $key): void;

    /**
     * Giá trị đã đặt tường minh (không gồm mặc định) — cho màn hình cấu hình.
     *
     * @return array<string, mixed> key => value
     */
    public function explicit(string $namespace): array;

    public function define(SettingDefinition $definition): void;

    /**
     * @return list<SettingDefinition>
     */
    public function definitions(?string $namespace = null): array;
}
