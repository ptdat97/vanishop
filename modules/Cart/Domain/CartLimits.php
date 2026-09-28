<?php

declare(strict_types=1);

namespace Modules\Cart\Domain;

use InvalidArgumentException;

/**
 * Giới hạn giỏ hàng (chống giỏ ảo khổng lồ, gom hàng). Trả mã lỗi thay vì ném để Application quyết định.
 */
final readonly class CartLimits
{
    public function __construct(
        public int $maxLineQuantity,
        public int $maxLines,
    ) {
        if ($maxLineQuantity < 1 || $maxLines < 1) {
            throw new InvalidArgumentException('Giới hạn giỏ phải >= 1.');
        }
    }

    /**
     * @return string|null mã lỗi "quantity_invalid" | "quantity_limit" | null nếu hợp lệ
     */
    public function checkQuantity(int $quantity): ?string
    {
        return match (true) {
            $quantity < 1 => 'quantity_invalid',
            $quantity > $this->maxLineQuantity => 'quantity_limit',
            default => null,
        };
    }

    public function canAddLine(int $currentLines): bool
    {
        return $currentLines < $this->maxLines;
    }

    /**
     * Số lượng khi gộp hai dòng cùng variant: cộng dồn rồi kẹp theo giới hạn dòng và số có thể bán.
     */
    public function mergedQuantity(int $target, int $source, int $available): int
    {
        return max(0, min($target + $source, $this->maxLineQuantity, max($available, $target)));
    }
}
