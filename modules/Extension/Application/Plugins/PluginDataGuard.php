<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Plugins;

use Illuminate\Support\Facades\Schema;
use Modules\Extension\Domain\Plugin\PluginManifest;

/**
 * Lý do không được xoá dữ liệu của plugin (`uninstall --purge`, 0.3.24):
 * - plugin khác đang cài khai báo `data.references` trỏ vào bảng của plugin này;
 * - khoá ngoại thật trong DB từ bảng không thuộc plugin trỏ vào bảng của plugin;
 * - bảng `retained` (chứng từ/pháp lý) khi chưa xác nhận `--drop-retained`.
 */
final class PluginDataGuard
{
    /**
     * @param  array<string, PluginManifest>  $installed  manifest các plugin đang cài (kể cả plugin đang gỡ)
     * @return list<string>
     */
    public function purgeBlockers(PluginManifest $manifest, array $installed, bool $dropRetained): array
    {
        $owned = $manifest->data->owned;
        $reasons = [];

        foreach ($installed as $id => $other) {
            if ($id === $manifest->id) {
                continue;
            }
            foreach ($other->data->references as $from => $to) {
                if (in_array(explode('.', $to)[0], $owned, true)) {
                    $reasons[] = "plugin {$id} tham chiếu {$to} (cột {$from})";
                }
            }
        }

        if ($owned !== []) {
            foreach (Schema::getTables() as $table) {
                $name = (string) $table['name'];
                if (in_array($name, $owned, true)) {
                    continue;
                }
                foreach (Schema::getForeignKeys($name) as $key) {
                    if (in_array((string) $key['foreign_table'], $owned, true)) {
                        $reasons[] = "khoá ngoại {$name}(".implode(', ', $key['columns']).") → {$key['foreign_table']}";
                    }
                }
            }
        }

        if ($manifest->data->retained !== [] && ! $dropRetained) {
            $reasons[] = 'dữ liệu phải lưu giữ: '.implode(', ', $manifest->data->retained).' — xuất/lưu trữ trước, rồi xác nhận --drop-retained';
        }

        return $reasons;
    }
}
