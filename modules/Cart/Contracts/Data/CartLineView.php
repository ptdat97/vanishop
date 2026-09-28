<?php

declare(strict_types=1);

namespace Modules\Cart\Contracts\Data;

use Modules\Catalog\Contracts\Data\SellableVariant;
use Modules\Shared\Domain\Money\Money;

/**
 * Dòng giỏ đã ghép giá hiện tại và tình trạng hàng. Không chứa số tồn chính xác.
 */
final readonly class CartLineView
{
    public const ISSUE_UNAVAILABLE = 'unavailable';

    public const ISSUE_INSUFFICIENT_STOCK = 'insufficient_stock';

    public const ISSUE_PRICE_CHANGED = 'price_changed';

    /**
     * @param  SellableVariant|null  $variant  null khi sản phẩm đã ngừng bán/ẩn
     * @param  list<string>  $issues
     */
    public function __construct(
        public int $id,
        public int $variantId,
        public int $quantity,
        public ?SellableVariant $variant,
        public ?Money $unitPrice,
        public ?Money $compareAt,
        public ?Money $snapshotPrice,
        public ?Money $lineTotal,
        public array $issues,
    ) {}

    public function blocksCheckout(): bool
    {
        return array_intersect($this->issues, [self::ISSUE_UNAVAILABLE, self::ISSUE_INSUFFICIENT_STOCK]) !== [];
    }
}
