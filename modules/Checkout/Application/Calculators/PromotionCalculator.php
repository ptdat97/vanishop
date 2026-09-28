<?php

declare(strict_types=1);

namespace Modules\Checkout\Application\Calculators;

use Modules\Checkout\Contracts\Data\Adjustment;
use Modules\Checkout\Contracts\Data\TotalsContext;
use Modules\Checkout\Contracts\Data\TotalsLine;
use Modules\Checkout\Contracts\TotalsCalculator;
use Modules\Promotion\Contracts\Data\PromotionContext;
use Modules\Promotion\Contracts\Data\PromotionLine;
use Modules\Promotion\Contracts\PromotionEngine;

final class PromotionCalculator implements TotalsCalculator
{
    public function __construct(private readonly PromotionEngine $engine) {}

    public function code(): string
    {
        return 'promotion';
    }

    public function priority(): int
    {
        return 200;
    }

    public function calculate(TotalsContext $context): TotalsContext
    {
        $result = $this->engine->evaluate(new PromotionContext(
            $context->channelId,
            $context->customerId,
            $context->currencyCode,
            array_map(fn (TotalsLine $line): PromotionLine => new PromotionLine($line->key, $line->brandId, $line->styleId, $line->quantity, $line->unitPrice, $line->total()), $context->lines),
            $context->voucherCodes,
            $context->now,
            $context->attributes,
        ));

        $discounts = $result->discountByLine();
        $context = $context
            ->withLines(array_map(fn (TotalsLine $line): TotalsLine => $line->withDiscount($line->discount->add($context->money($discounts[$line->key] ?? 0))), $context->lines))
            ->withPromotions($result);

        foreach ($result->applied as $applied) {
            $context = $context->withAdjustment(new Adjustment(
                type: 'promotion',
                source: 'core',
                code: $applied->voucherCode,
                label: $applied->name,
                amount: $applied->total->negate(),
                meta: ['promotion_id' => $applied->promotionId, 'voucher_id' => $applied->voucherId],
            ));
        }

        return $context;
    }
}
