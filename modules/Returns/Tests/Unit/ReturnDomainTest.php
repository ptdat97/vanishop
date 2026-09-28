<?php

use Modules\Returns\Domain\RefundCalculator;
use Modules\Returns\Domain\ReturnStatus as R;
use Modules\Shared\Domain\Money\Money;

it('chuyển trạng thái đổi/trả hợp lệ', function () {
    expect(R::Requested->canMoveTo(R::Approved))->toBeTrue()
        ->and(R::Requested->canMoveTo(R::Received))->toBeFalse()
        ->and(R::Approved->canMoveTo(R::Received))->toBeTrue()
        ->and(R::InTransit->canMoveTo(R::Cancelled))->toBeFalse()
        ->and(R::Received->canMoveTo(R::Resolved))->toBeTrue()
        ->and(R::Resolved->canMoveTo(R::Rejected))->toBeFalse()
        ->and(R::Rejected->countsTowardsLimit())->toBeFalse()
        ->and(R::Resolved->countsTowardsLimit())->toBeTrue();
});

it('tiền hoàn theo đơn vị: trả nhiều lần tổng không vượt thành tiền dòng', function () {
    $total = Money::vnd(100_001);

    $first = RefundCalculator::forUnits($total, 3, 0, 1);
    $rest = RefundCalculator::forUnits($total, 3, 1, 2);

    expect($first->amount + $rest->amount)->toBe(100_001)
        ->and(RefundCalculator::forUnits(Money::vnd(540_000), 2, 0, 1)->amount)->toBe(270_000);
    expect(fn () => RefundCalculator::forUnits($total, 3, 2, 2))->toThrow(InvalidArgumentException::class);
});
