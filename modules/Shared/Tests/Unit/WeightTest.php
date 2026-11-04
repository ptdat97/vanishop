<?php

use Modules\Shared\Domain\Weight\Weight;

it('lưu gram nguyên; nhập từ kg/g/lb/oz làm tròn tới gram; chuỗi dùng dấu phẩy thập phân', function () {
    expect(Weight::of(1.2, 'kg')->grams)->toBe(1_200)
        ->and(Weight::of('0,35', 'kg')->grams)->toBe(350)
        ->and(Weight::of(1, 'lb')->grams)->toBe(454)
        ->and(Weight::of(2, 'oz')->grams)->toBe(57)
        ->and(Weight::of(250)->grams)->toBe(250)
        ->and(Weight::grams(400)->multiply(3)->add(Weight::grams(100))->grams)->toBe(1_300)
        ->and(Weight::grams(1_500)->kilograms())->toBe(1.5);
});

it('từ chối âm, đơn vị lạ, giá trị không phải số', function () {
    expect(fn () => Weight::grams(-1))->toThrow(InvalidArgumentException::class)
        ->and(fn () => Weight::of(1, 'ton'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => Weight::of('nặng', 'kg'))->toThrow(InvalidArgumentException::class);
});
