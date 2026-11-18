<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Plugins;

/**
 * Nâng plugin khi version đã cài trùng version trong code: không có gì để làm. Vẫn là PluginOperationFailed để Admin
 * báo như cũ; `vani:plugin:upgrade` coi là thành công (vòng nâng mọi plugin khi deploy không báo lỗi giả).
 */
final class PluginAlreadyCurrent extends PluginOperationFailed {}
