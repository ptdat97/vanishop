<?php

declare(strict_types=1);

namespace Modules\Checkout\Testing;

use Closure;
use Modules\Checkout\Contracts\Data\TotalsContext;
use Modules\Checkout\Contracts\TaxCalculator;

/**
 * Contract test cho TaxCalculator. Kiểm tra: mã ổn định; chỉ trả thuế cho dòng có trong giỏ; thuế suất 0–100%;
 * tiền thuế nguyên, không âm, không vượt thành tiền dòng; cùng đầu vào cho cùng kết quả.
 *
 *   TaxCalculatorContract::define('vani.tax-xyz', fn () => new XyzTax(...));
 */
final class TaxCalculatorContract
{
    /**
     * @param  Closure(): TaxCalculator  $calculator
     * @param  (Closure(): TotalsContext)|null  $context
     */
    public static function define(string $label, Closure $calculator, ?Closure $context = null): void
    {
        $context ??= fn (): TotalsContext => Samples::totalsContext();

        describe("TaxCalculator contract: {$label}", function () use ($calculator, $context): void {
            it('có mã ổn định', fn () => expect($calculator()->code())->toMatch('/^[a-z][a-z0-9_]*$/'));

            it('chỉ trả thuế hợp lệ cho các dòng của giỏ, xác định', function () use ($calculator, $context): void {
                $input = $context();
                $totals = [];
                foreach ($input->lines as $line) {
                    $totals[$line->key] = $line->total()->amount;
                }
                $result = $calculator()->calculate($input);

                expect(array_diff(array_keys($result), array_keys($totals)))->toBe([])
                    ->and($calculator()->calculate($context()))->toBe($result);
                foreach ($result as $key => $tax) {
                    expect($tax['rate_bp'])->toBeInt()->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(10_000)
                        ->and($tax['amount'])->toBeInt()->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual($totals[$key]);
                }
            });
        });
    }
}
