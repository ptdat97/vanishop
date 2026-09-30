<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts;

/**
 * Interface bổ sung tuỳ chọn cho SearchProvider có chỉ mục ngoài cần cấu hình (thuộc tính lọc/sắp xếp…).
 * `vani:search:reindex --setup` gọi `setupIndex()` nếu provider implement interface này.
 */
interface ConfigurableSearchIndex
{
    public function setupIndex(): void;
}
