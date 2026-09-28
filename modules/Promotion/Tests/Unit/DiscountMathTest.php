<?php

use Modules\Promotion\Domain\DiscountMath;
use Modules\Shared\Domain\Money\Money;

it('giảm theo % trên từng dòng, làm tròn half-up', function () {
    $result = DiscountMath::percentOff([1 => Money::vnd(199_000), 2 => Money::vnd(50_005)], 1500);

    expect($result[1]->amount)->toBe(29_850)->and($result[2]->amount)->toBe(7_501);
});

it('giảm số tiền: phân bổ bảo toàn tổng, không vượt tổng còn lại', function () {
    $result = DiscountMath::amountOff([1 => Money::vnd(100_000), 2 => Money::vnd(200_000), 3 => Money::vnd(100_000)], Money::vnd(10_001));

    expect(array_map(fn ($money) => $money->amount, $result))->toBe([1 => 2_500, 2 => 5_001, 3 => 2_500]);

    $capped = DiscountMath::amountOff([1 => Money::vnd(30_000)], Money::vnd(50_000));
    expect($capped[1]->amount)->toBe(30_000);
});

it('kẹp theo giá sàn tính cả phần đã giảm trước đó', function () {
    $result = DiscountMath::capToFloor(
        [1 => Money::vnd(40_000), 2 => Money::vnd(10_000)],
        [1 => Money::vnd(100_000), 2 => Money::vnd(100_000)],
        [1 => Money::vnd(20_000), 2 => Money::vnd(0)],
        5000,
    );

    expect($result[1]->amount)->toBe(30_000)->and($result[2]->amount)->toBe(10_000);
});
