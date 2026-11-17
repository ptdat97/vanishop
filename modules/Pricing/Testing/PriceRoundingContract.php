<?php

declare(strict_types=1);

namespace Modules\Pricing\Testing;

use Closure;
use Modules\Pricing\Contracts\PriceRounding;

/**
 * Contract test cho PriceRounding. Kiểm tra: mã, nhãn; với giá ≥ 0: kết quả ≥ 0, làm tròn lại không đổi, không giảm thứ tự
 * (giá cao hơn không thành thấp hơn); xác định.
 */
final class PriceRoundingContract
{
    /**
     * @param  Closure(): PriceRounding  $rounding
     */
    public static function define(string $label, Closure $rounding): void
    {
        describe("PriceRounding contract: {$label}", function () use ($rounding): void {
            it('mã, nhãn', fn () => expect($rounding()->code())->toMatch('/^[a-z][a-z0-9_.-]*$/')->and($rounding()->label())->not->toBe(''));

            it('không âm, làm tròn lại không đổi, giữ thứ tự', function () use ($rounding): void {
                $previous = -1;
                foreach ([0, 1, 999, 1_000, 12_345, 99_999, 100_000, 249_500, 1_000_000] as $amount) {
                    $rounded = $rounding()->round($amount);
                    expect($rounded)->toBeGreaterThanOrEqual(0)
                        ->and($rounding()->round($rounded))->toBe($rounded)
                        ->and($rounded)->toBeGreaterThanOrEqual($previous);
                    $previous = $rounded;
                }
            });
        });
    }
}
