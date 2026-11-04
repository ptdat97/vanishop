<?php

declare(strict_types=1);

namespace Modules\Extension\Contracts;

use Modules\Extension\Contracts\Data\PluginInfo;

/**
 * Service contract chỉ đọc (0.3.69, ADR-037): plugin khác, theme, view hỏi thông tin plugin và URL tài nguyên tĩnh của
 * plugin (`vani_plugin()`, `vani_asset()`).
 */
interface PluginDirectory
{
    /**
     * null khi không có plugin (manifest) này.
     */
    public function find(string $pluginId): ?PluginInfo;

    /**
     * URL tài nguyên tĩnh đã publish của plugin (tên file có hash, cache dài hạn); null khi plugin không khai báo
     * `assets`, file không có, hoặc chưa publish (`php artisan vani:plugin:assets`).
     *
     * @param  string  $path  đường dẫn trong thư mục `assets` của plugin, vd. `css/widget.css`
     */
    public function asset(string $pluginId, string $path): ?string;
}
