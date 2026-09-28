<?php

use Modules\Shared\Domain\Money\Money;

it('tách VAT đã gồm trong giá theo basis points', function () {
    expect(Money::vnd(110_000)->includedTax(1000)->amount)->toBe(10_000)
        ->and(Money::vnd(108_000)->includedTax(800)->amount)->toBe(8_000)
        ->and(Money::vnd(590_000)->includedTax(1000)->amount)->toBe(53_636)
        ->and(Money::vnd(590_000)->includedTax(0)->isZero())->toBeTrue();
});
