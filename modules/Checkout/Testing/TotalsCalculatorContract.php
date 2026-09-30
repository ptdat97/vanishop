<?php

declare(strict_types=1);

namespace Modules\Checkout\Testing;

use Closure;
use Modules\Checkout\Contracts\Data\Adjustment;
use Modules\Checkout\Contracts\Data\TotalsContext;
use Modules\Checkout\Contracts\TotalsCalculator;

/**
 * Contract test cho TotalsCalculator. Kiểm tra: mã ổn định; plugin dùng priority 300–399; không đổi dòng
 * (variant, số lượng, đơn giá) và tiền tệ; adjustment cùng tiền tệ; xác định.
 *
 *   TotalsCalculatorContract::define('vani.loyalty', fn () => new PointsRedemption(...));
 */
final class TotalsCalculatorContract
{
    /**
     * @param  Closure(): TotalsCalculator  $calculator
     * @param  (Closure(): TotalsContext)|null  $context
     */
    public static function define(string $label, Closure $calculator, ?Closure $context = null, bool $plugin = true): void
    {
        $context ??= fn (): TotalsContext => Samples::totalsContext();

        describe("TotalsCalculator contract: {$label}", function () use ($calculator, $context, $plugin): void {
            it('có mã ổn định và priority đúng dải', function () use ($calculator, $plugin): void {
                expect($calculator()->code())->toMatch('/^[a-z][a-z0-9_.]*$/');
                if ($plugin) {
                    expect($calculator()->priority())->toBeGreaterThanOrEqual(300)->toBeLessThanOrEqual(399);
                }
            });

            it('không đổi dòng/tiền tệ; adjustment cùng tiền tệ; xác định', function () use ($calculator, $context): void {
                $input = $context();
                $output = $calculator()->calculate($input);
                $shape = fn (TotalsContext $totals): array => array_map(fn ($line): array => [$line->key, $line->variantId, $line->quantity, $line->unitPrice->amount], $totals->lines);

                expect($output)->toBeInstanceOf(TotalsContext::class)
                    ->and($output->currencyCode)->toBe($input->currencyCode)
                    ->and($shape($output))->toBe($shape($input))
                    ->and(array_map(fn (Adjustment $adjustment): string => $adjustment->amount->currency->code, $output->adjustments))->each->toBe($input->currencyCode)
                    ->and($calculator()->calculate($context()))->toEqual($output);
            });
        });
    }
}
