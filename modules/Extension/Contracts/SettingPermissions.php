<?php

declare(strict_types=1);

namespace Modules\Extension\Contracts;

use Modules\Tenancy\Contracts\Data\SettingGroup;

/**
 * Quyền của Admin → Cài đặt: `settings.manage` = toàn bộ (kể cả ô bí mật); từng nhóm của Cài đặt hệ thống
 * `settings.<nhóm>` (vd. `settings.seo`); từng plugin `settings.plugin.<plugin id>`; ô `secret` (mật khẩu, khoá API)
 * cần thêm `settings.secrets`.
 */
final class SettingPermissions
{
    public const ALL = 'settings.manage';

    public const SECRETS = 'settings.secrets';

    public static function forGroup(SettingGroup $group): string
    {
        return "settings.{$group->value}";
    }

    public static function forPlugin(string $pluginId): string
    {
        return "settings.plugin.{$pluginId}";
    }
}
