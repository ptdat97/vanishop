<?php

use Modules\Shared\Domain\Money\Currency;
use Modules\Shared\Domain\Money\CurrencyMismatch;
use Modules\Shared\Domain\Money\Money;
use Modules\Shared\Domain\Money\UnsupportedCurrency;
use Modules\Shared\Support\MoneyFormatter;

it('cộng trừ nhân trên cùng tiền tệ', function () {
    $money = Money::vnd(159_000)->add(Money::vnd(1_000))->subtract(Money::vnd(10_000))->multiply(3);

    expect($money->amount)->toBe(450_000)
        ->and($money->currency->code)->toBe('VND');
});

it('không cho tính toán khác tiền tệ', function () {
    Money::vnd(1_000)->add(Money::of(100, 'USD'));
})->throws(CurrencyMismatch::class);

it('từ chối tiền tệ chưa hỗ trợ', function () {
    Currency::of('XYZ');
})->throws(UnsupportedCurrency::class);

it('tính phần trăm bằng basis points và làm tròn half-up', function (int $amount, int $basisPoints, int $expected) {
    expect(Money::vnd($amount)->percentage($basisPoints)->amount)->toBe($expected);
})->with([
    '10% của 159.000' => [159_000, 1_000, 15_900],
    '12,5% của 99.999 làm tròn lên' => [99_999, 1_250, 12_500],
    '0,5 ₫ làm tròn xa 0' => [1, 5_000, 1],
    'số âm làm tròn xa 0' => [-1, 5_000, -1],
]);

it('hỗ trợ làm tròn half-even và towards-zero', function () {
    expect(Money::vnd(5)->percentage(5_000, RoundingMode::HalfEven)->amount)->toBe(2)
        ->and(Money::vnd(7)->percentage(5_000, RoundingMode::HalfEven)->amount)->toBe(4)
        ->and(Money::vnd(19)->percentage(5_000, RoundingMode::TowardsZero)->amount)->toBe(9);
});

it('làm tròn đến bước 1.000 ₫', function () {
    expect(Money::vnd(159_499)->roundToStep(1_000)->amount)->toBe(159_000)
        ->and(Money::vnd(159_500)->roundToStep(1_000)->amount)->toBe(160_000);
});

it('phân bổ theo largest remainder, phần dư cho phần có số lẻ lớn nhất', function () {
    $parts = Money::vnd(10_001)->allocate([1, 2, 1]);

    expect(array_map(fn (Money $m) => $m->amount, $parts))->toBe([2_500, 5_001, 2_500]);
});

it('phân bổ luôn bảo toàn tổng', function (int $amount, array $weights) {
    $parts = Money::vnd($amount)->allocate($weights);

    expect(array_sum(array_map(fn (Money $m) => $m->amount, $parts)))->toBe($amount)
        ->and($parts)->toHaveCount(count($weights));
})->with([
    [100, [1, 1, 1]],
    [-50_000, [3, 7]],
    [7, [0, 5, 5]],
    [1_234_567, [13, 29, 1, 57]],
    [0, [1, 2]],
]);

it('từ chối trọng số không hợp lệ', function (array $weights) {
    Money::vnd(100)->allocate($weights);
})->with([[[]], [[0, 0]], [[1, -1]]])->throws(InvalidArgumentException::class);

it('định dạng tiền theo cấu hình (kiểu Việt Nam); mặc định trung lập', function () {
    expect((new MoneyFormatter)->format(Money::vnd(1_250_000)))->toBe('1,250,000 VND');

    $formatter = new MoneyFormatter('.', ',', ['VND' => '₫', 'USD' => '$']);

    expect($formatter->format(Money::vnd(1_250_000)))->toBe('1.250.000 ₫')
        ->and($formatter->format(Money::vnd(-5_000)))->toBe('-5.000 ₫')
        ->and($formatter->format(Money::of(1_250, 'USD')))->toBe('12,50 $')
        ->and($formatter->toArray(Money::vnd(159_000)))->toBe(['amount' => 159_000, 'currency' => 'VND', 'formatted' => '159.000 ₫']);
});
