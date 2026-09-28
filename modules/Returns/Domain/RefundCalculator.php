<?php

declare(strict_types=1);

namespace Modules\Returns\Domain;

use Modules\Shared\Domain\Money\Money;

/**
 * Tiền hoàn cho k sản phẩm của một dòng = phần của k đơn vị kế tiếp khi chia thành tiền dòng (đã trừ giảm giá
 * phân bổ) đều cho số lượng bằng largest remainder. Trả nhiều lần không bao giờ vượt thành tiền dòng.
 */
final class RefundCalculator
{
    public static function forUnits(Money $lineTotal, int $lineQuantity, int $alreadyReturned, int $quantity): Money
    {
        if ($quantity < 1 || $lineQuantity < 1 || $alreadyReturned + $quantity > $lineQuantity) {
            throw new \InvalidArgumentException('Số lượng trả không hợp lệ.');
        }

        $units = $lineTotal->allocate(array_fill(0, $lineQuantity, 1));
        $amount = 0;
        for ($i = $alreadyReturned; $i < $alreadyReturned + $quantity; $i++) {
            $amount += $units[$i]->amount;
        }

        return Money::of($amount, $lineTotal->currency);
    }
}
