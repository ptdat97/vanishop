<?php

use Modules\Cart\Domain\CartLimits;

it('kiểm tra số lượng dòng', function () {
    $limits = new CartLimits(maxLineQuantity: 5, maxLines: 2);

    expect($limits->checkQuantity(0))->toBe('quantity_invalid')
        ->and($limits->checkQuantity(6))->toBe('quantity_limit')
        ->and($limits->checkQuantity(5))->toBeNull()
        ->and($limits->canAddLine(1))->toBeTrue()
        ->and($limits->canAddLine(2))->toBeFalse();
});

it('gộp dòng: cộng dồn, kẹp theo giới hạn và số có thể bán, không giảm dòng đích', function () {
    $limits = new CartLimits(maxLineQuantity: 5, maxLines: 10);

    expect($limits->mergedQuantity(1, 2, 10))->toBe(3)
        ->and($limits->mergedQuantity(3, 4, 10))->toBe(5)
        ->and($limits->mergedQuantity(1, 4, 2))->toBe(2)
        ->and($limits->mergedQuantity(3, 1, 0))->toBe(3)
        ->and($limits->mergedQuantity(0, 2, 0))->toBe(0);
});

it('không nhận giới hạn < 1', function () {
    new CartLimits(0, 1);
})->throws(InvalidArgumentException::class);
