<?php

declare(strict_types=1);

namespace Modules\Pricing\Contracts\Data;

use Modules\Shared\Contracts\PluginApi;

/**
 * Một suất của bảng giá (0.5.117): phạm vi `list` (cả bảng, `scopeId` = 0) | `product` (mọi variant của sản phẩm cộng chung) |
 * `variant`; `limit` = tổng số lượng bán theo giá này (null = không giới hạn tổng), `perCustomerLimit` = tối đa mỗi khách.
 */
#[PluginApi]
final readonly class PriceQuotaRule
{
    public const SCOPE_LIST = 'list';

    public const SCOPE_PRODUCT = 'product';

    public const SCOPE_VARIANT = 'variant';

    public function __construct(
        public string $scope,
        public int $scopeId,
        public ?int $limit,
        public ?int $perCustomerLimit = null,
    ) {}
}
