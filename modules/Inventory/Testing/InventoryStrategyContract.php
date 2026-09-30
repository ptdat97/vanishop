<?php

declare(strict_types=1);

namespace Modules\Inventory\Testing;

use Closure;
use Modules\Inventory\Contracts\InventoryStrategy;

/**
 * Contract test cho InventoryStrategy. Kiểm tra: mã ổn định; chỉ trả variant được đưa vào; ATS nguyên, không âm,
 * KHÔNG vượt ATS chuẩn (strategy chỉ được giảm — Core cũng kẹp, nhưng strategy không được dựa vào điều đó); xác định.
 */
final class InventoryStrategyContract
{
    /**
     * @param  Closure(): InventoryStrategy  $strategy
     */
    public static function define(string $label, Closure $strategy): void
    {
        describe("InventoryStrategy contract: {$label}", function () use ($strategy): void {
            it('có mã ổn định', fn () => expect($strategy()->code())->toMatch('/^[a-z][a-z0-9_]*$/'));

            it('chỉ giảm ATS, không âm, xác định', function () use ($strategy): void {
                $standard = [101 => 5, 102 => 0, 103 => 120];
                $adjusted = $strategy()->adjust($standard, 1);

                expect(array_diff(array_keys($adjusted), array_keys($standard)))->toBe([])
                    ->and($strategy()->adjust($standard, 1))->toBe($adjusted);
                foreach ($adjusted as $variantId => $ats) {
                    expect($ats)->toBeInt()->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual($standard[$variantId]);
                }
            });
        });
    }
}
