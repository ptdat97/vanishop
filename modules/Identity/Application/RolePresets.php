<?php

declare(strict_types=1);

namespace Modules\Identity\Application;

use Modules\Identity\Contracts\PermissionRegistry;

/**
 * Vai trò mẫu cho từng team (Admin → Vai trò → Thêm vai trò → "Bắt đầu từ mẫu"): chọn mẫu thì điền sẵn tên, mã và các
 * quyền khai `presets` tương ứng; chủ shop vẫn chỉnh được trước khi lưu. Owner dùng vai trò hệ thống `owner_admin`.
 */
final class RolePresets
{
    public const KEYS = [
        PermissionRegistry::PRESET_ORDER_OPS,
        PermissionRegistry::PRESET_MARKETING,
        PermissionRegistry::PRESET_CONTENT,
        PermissionRegistry::PRESET_ANALYST,
    ];

    public function __construct(private readonly PermissionRegistry $registry) {}

    /**
     * @return list<array{key: string, code: string, name: string, description: string, permissions: list<string>}>
     */
    public function all(): array
    {
        return array_map(fn (string $key): array => [
            'key' => $key,
            'code' => $key,
            'name' => (string) __("identity::texts.role_presets.{$key}.name"),
            'description' => (string) __("identity::texts.role_presets.{$key}.description"),
            'permissions' => $this->registry->preset($key),
        ], self::KEYS);
    }
}
