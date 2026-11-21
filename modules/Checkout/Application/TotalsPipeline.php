<?php

declare(strict_types=1);

namespace Modules\Checkout\Application;

use Modules\Checkout\Contracts\Data\Totals;
use Modules\Checkout\Contracts\Data\TotalsContext;
use Modules\Checkout\Contracts\Data\TotalsLine;
use Modules\Checkout\Contracts\TotalsCalculator;
use Modules\Extension\Contracts\Extensions;
use Modules\Shared\Domain\Money\Money;

/**
 * Chạy các TotalsCalculator theo priority (docs/03-domains/cart-checkout.md §2). Cùng một pipeline cho
 * xem tổng (quote) và PlaceOrder.
 */
final class TotalsPipeline
{
    /** Từ priority này trở lên là chốt chặn: hook `vani.service.checkout.totals` chạy ngay trước. */
    public const GUARD_PRIORITY = 900;

    /** Hook plugin sửa kết quả: chạy sau mọi calculator, trước chốt chặn (guard ≥ 900) — giảm giá sai vẫn bị cắt. */
    public const TOTALS_HOOK = 'vani.service.checkout.totals';

    /** Dải priority dành cho calculator của plugin (sau khuyến mãi, trước phí giao). */
    public const PLUGIN_PRIORITIES = [300, 399];

    public function __construct(private readonly Extensions $extensions) {}

    public function run(TotalsContext $context): Totals
    {
        foreach ($this->steps() as $step) {
            $context = $step instanceof TotalsCalculator ? $step->calculate($context) : vani_filter($step, $context);
        }

        $sum = fn (callable $pick): Money => array_reduce($context->lines, fn (Money $carry, TotalsLine $line): Money => $carry->add($pick($line)), $context->money(0));
        $shipping = $context->shipping?->fee ?? $context->money(0);

        return new Totals(
            currencyCode: $context->currencyCode,
            lines: $context->lines,
            adjustments: $context->adjustments,
            subtotal: $sum(fn (TotalsLine $line): Money => $line->subtotal),
            discount: $sum(fn (TotalsLine $line): Money => $line->discount),
            shipping: $context->shipping,
            tax: $sum(fn (TotalsLine $line): Money => $line->tax ?? $context->money(0)),
            grandTotal: $context->linesTotal()->add($shipping),
            rejectedVouchers: $context->promotions->rejectedVouchers ?? [],
            promotions: $context->promotions,
        );
    }

    /**
     * Thứ tự chạy thật: calculator theo (priority, code), hook {@see self::TOTALS_HOOK} chen ngay trước calculator đầu tiên
     * có priority ≥ {@see self::GUARD_PRIORITY} (không có thì ở cuối). `vani:flows` in đúng danh sách này.
     *
     * @return list<TotalsCalculator|string> calculator, hoặc tên hook
     */
    public function steps(): array
    {
        $calculators = array_values(array_filter($this->extensions->tagged(TotalsCalculator::TAG), fn (object $calculator): bool => $calculator instanceof TotalsCalculator));
        usort($calculators, fn (TotalsCalculator $a, TotalsCalculator $b): int => [$a->priority(), $a->code()] <=> [$b->priority(), $b->code()]);

        $steps = [];
        foreach ($calculators as $calculator) {
            if (! in_array(self::TOTALS_HOOK, $steps, true) && $calculator->priority() >= self::GUARD_PRIORITY) {
                $steps[] = self::TOTALS_HOOK;
            }
            $steps[] = $calculator;
        }
        if (! in_array(self::TOTALS_HOOK, $steps, true)) {
            $steps[] = self::TOTALS_HOOK;
        }

        return $steps;
    }

    /**
     * Calculator của plugin đặt sai chỗ (`vani:plugin:doctor`): priority ngoài {@see self::PLUGIN_PRIORITIES} — chạy trước
     * tạm tính hoặc sau chốt chặn — hoặc trùng mã với calculator khác.
     *
     * @return list<string> "plugin:mã (priority)"
     */
    public function misplacedPluginCalculators(): array
    {
        $codes = [];
        $misplaced = [];
        foreach ($this->steps() as $step) {
            if (! $step instanceof TotalsCalculator) {
                continue;
            }
            $owner = $this->extensions->ownerOf($step);
            [$min, $max] = self::PLUGIN_PRIORITIES;
            if ($owner !== null && ($step->priority() < $min || $step->priority() > $max || isset($codes[$step->code()]))) {
                $misplaced[] = "{$owner}:{$step->code()} ({$step->priority()})";
            }
            $codes[$step->code()] = true;
        }

        return $misplaced;
    }
}
