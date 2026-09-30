<?php

declare(strict_types=1);

namespace Modules\Pricing\Testing;

use Closure;
use Modules\Pricing\Contracts\Data\PricingContext;
use Modules\Pricing\Contracts\Data\ResolvedPrice;
use Modules\Pricing\Contracts\PricingStrategy;

/**
 * Contract test cho PricingStrategy. Plugin cung cấp `scenario` (tạo dữ liệu giá, trả [variantIds, PricingContext]).
 * Kiểm tra: mã ổn định; chỉ trả variant được hỏi, khoá khớp variantId; giá dương cùng tiền tệ; giá gốc (nếu có)
 * lớn hơn giá bán; % giảm 1–99; variant không tồn tại bị bỏ qua; xác định.
 */
final class PricingStrategyContract
{
    /**
     * @param  Closure(): PricingStrategy  $strategy
     * @param  Closure(): array{0: list<int>, 1: PricingContext}  $scenario
     */
    public static function define(string $label, Closure $strategy, Closure $scenario): void
    {
        describe("PricingStrategy contract: {$label}", function () use ($strategy, $scenario): void {
            it('có mã ổn định', fn () => expect($strategy()->code())->toMatch('/^[a-z][a-z0-9_]*$/'));

            it('giá hợp lệ cho đúng variant được hỏi, xác định', function () use ($strategy, $scenario): void {
                [$variantIds, $context] = $scenario();
                $prices = $strategy()->resolve([...$variantIds, 999_999_999], $context);

                expect(array_diff(array_keys($prices), $variantIds))->toBe([])->and($prices)->not->toBe([]);
                foreach ($prices as $variantId => $price) {
                    expect($price)->toBeInstanceOf(ResolvedPrice::class)
                        ->and($price->variantId)->toBe($variantId)
                        ->and($price->amount->amount)->toBeGreaterThan(0);
                    if ($price->compareAt !== null) {
                        expect($price->compareAt->amount)->toBeGreaterThan($price->amount->amount)
                            ->and($price->compareAt->currency->code)->toBe($price->amount->currency->code);
                    }
                    if ($price->discountPercent !== null) {
                        expect($price->discountPercent)->toBeGreaterThanOrEqual(1)->toBeLessThanOrEqual(99);
                    }
                }
                expect($strategy()->resolve([...$variantIds, 999_999_999], $context))->toEqual($prices);
            });
        });
    }
}
