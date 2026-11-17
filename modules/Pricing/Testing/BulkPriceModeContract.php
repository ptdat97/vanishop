<?php

declare(strict_types=1);

namespace Modules\Pricing\Testing;

use Closure;
use Modules\Pricing\Contracts\BulkPriceMode;

/**
 * Contract test cho BulkPriceMode. Kiểm tra: mã, nhãn, gợi ý; danh sách rỗng → rỗng; chỉ trả SKU được hỏi; giá int ≥ 0 hoặc
 * null (không tính được); xác định. Bên gọi chuẩn bị dữ liệu để ít nhất một SKU tính được.
 */
final class BulkPriceModeContract
{
    /**
     * @param  Closure(): BulkPriceMode  $mode
     * @param  Closure(): array{0: list<int>, 1: float, 2: array<int, int>}  $input  [SKU, giá trị nhập, giá niêm yết]
     */
    public static function define(string $label, Closure $mode, Closure $input): void
    {
        describe("BulkPriceMode contract: {$label}", function () use ($mode, $input): void {
            it('mã, nhãn, gợi ý', fn () => expect($mode()->code())->toMatch('/^[a-z][a-z0-9_.-]*$/')
                ->and($mode()->label())->not->toBe('')->and($mode()->hint())->not->toBe(''));

            it('chỉ trả SKU được hỏi; int ≥ 0 hoặc null; xác định', function () use ($mode, $input): void {
                [$ids, $value, $listAmounts] = $input();
                expect($mode()->compute([], $value, []))->toBe([]);
                $prices = $mode()->compute($ids, $value, $listAmounts);
                expect(array_diff(array_keys($prices), $ids))->toBe([])
                    ->and($mode()->compute($ids, $value, $listAmounts))->toBe($prices)
                    ->and(array_filter($prices, fn (?int $price): bool => $price !== null))->not->toBe([]);
                foreach ($prices as $price) {
                    expect($price === null || (is_int($price) && $price >= 0))->toBeTrue();
                }
            });
        });
    }
}
