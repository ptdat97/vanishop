<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts;

use Modules\Shared\Contracts\PluginApi;

/**
 * Interface bổ sung tuỳ chọn cho SearchProvider có chỉ mục ngoài cần cấu hình (thuộc tính lọc/sắp xếp…).
 * `vani:search:reindex --setup` gọi `setupIndex()` nếu provider implement interface này.
 */
#[PluginApi]
interface ConfigurableSearchIndex
{
    public function setupIndex(): void;
}
