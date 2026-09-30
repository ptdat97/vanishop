<?php

declare(strict_types=1);

namespace Modules\Tenancy\Contracts;

use Modules\Tenancy\Contracts\Data\SettingDefinition;
use Modules\Tenancy\Contracts\Data\SettingsScope;

/**
 * Service contract: cấu hình theo phạm vi (owner → pháp nhân → brand → kênh) cho Core và plugin.
 * `namespace` = `core` hoặc id plugin. Giá trị lưu dạng JSON; secret mã hoá bằng APP_KEY.
 */
interface Settings
{
    /**
     * Giá trị hiệu lực: phạm vi cụ thể nhất có đặt giá trị thắng; không có → default của định nghĩa → $default.
     */
    public function get(string $namespace, string $key, SettingsScope $scope, mixed $default = null): mixed;

    /**
     * `get()` với phạm vi của request/job hiện tại (CurrentContext: kênh + brand nếu chỉ một brand) — dùng trong
     * implementation của plugin.
     */
    public function current(string $namespace, string $key, mixed $default = null): mixed;

    public function set(string $namespace, string $key, mixed $value, string $scopeType, int $scopeId = 0): void;

    /** Xoá giá trị ở đúng phạm vi đó (phạm vi này lại kế thừa từ phạm vi cha). */
    public function forget(string $namespace, string $key, string $scopeType, int $scopeId = 0): void;

    /**
     * Giá trị đặt TRỰC TIẾP ở một phạm vi (không kế thừa) — cho màn hình cấu hình.
     *
     * @return array<string, mixed> key => value
     */
    public function explicit(string $namespace, string $scopeType, int $scopeId = 0): array;

    public function define(SettingDefinition $definition): void;

    /**
     * @return list<SettingDefinition>
     */
    public function definitions(?string $namespace = null): array;
}
