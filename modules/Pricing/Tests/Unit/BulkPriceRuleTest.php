<?php

use Modules\Pricing\Domain\BulkPriceRule;

it('tính giá mới từ giá niêm yết: giảm %, giảm số tiền, đồng giá; làm tròn 1.000đ; không âm; thiếu giá niêm yết → null', function (string $mode, int $value, int $roundTo, ?int $list, ?int $expected) {
    expect((new BulkPriceRule($mode, $value, $roundTo))->apply($list))->toBe($expected);
})->with([
    'giảm 30% làm tròn' => ['percent', 3000, 1000, 299_000, 209_000],
    'giảm 30% không làm tròn' => ['percent', 3000, 0, 299_000, 209_300],
    'giảm 100% → 0đ' => ['percent', 10_000, 1000, 299_000, 0],
    'giảm số tiền vượt giá → 0đ' => ['amount', 500_000, 1000, 299_000, 0],
    'đồng giá không làm tròn' => ['fixed', 199_500, 1000, null, 199_500],
    'đồng giá 0đ' => ['fixed', 0, 1000, 299_000, 0],
    'giảm % khi chưa có giá niêm yết' => ['percent', 1000, 1000, null, null],
]);

it('cảnh báo không chặn: 0đ, không thấp hơn giá niêm yết, giảm vượt mức trần; % giảm làm tròn xuống', function () {
    expect(BulkPriceRule::warnings(300_000, 0, 5000))->toBe(['zero', 'over_cap'])
        ->and(BulkPriceRule::warnings(300_000, 300_000, 5000))->toBe(['not_lower'])
        ->and(BulkPriceRule::warnings(300_000, 150_000, 5000))->toBe([])
        ->and(BulkPriceRule::warnings(300_000, 149_000, 10_000))->toBe([])
        ->and(BulkPriceRule::warnings(null, 0, 5000))->toBe(['zero'])
        ->and(BulkPriceRule::warnings(300_000, null, 5000))->toBe(['no_base'])
        ->and(BulkPriceRule::discountPercent(299_000, 209_000))->toBe(30)
        ->and(BulkPriceRule::discountPercent(300_000, 300_000))->toBeNull();
});

it('từ chối cách tính không hợp lệ', function () {
    expect(fn () => new BulkPriceRule('percent', 10_001))->toThrow(InvalidArgumentException::class)
        ->and(fn () => new BulkPriceRule('double', 1))->toThrow(InvalidArgumentException::class);
});
