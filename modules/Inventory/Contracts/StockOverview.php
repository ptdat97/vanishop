<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts;

use Modules\Shared\Contracts\PluginApi;

/**
 * Service contract (đọc, 0.5.97 — báo cáo hàng bán chậm, reference-parity L41): duyệt SKU đang bán còn tồn bán được online
 * (Σ tồn − giữ − an toàn ở kho giao online), theo lô id tăng dần, từ read model lưới tồn — không nạp mọi SKU. Sản phẩm
 * không theo dõi tồn không có mặt.
 */
#[PluginApi]
interface StockOverview
{
    /**
     * @return array<int, int> variant id => số bán được (> 0), id tăng dần, tối đa `$limit`; trang sau truyền id cuối
     */
    public function stockedVariants(int $afterVariantId = 0, int $limit = 1000): array;
}
