<?php

use Modules\Ordering\Domain\OrderNumber;

it('định dạng số đơn theo brand + kỳ + 6 số', function () {
    expect(OrderNumber::format('lm', OrderNumber::period(new DateTimeImmutable('2026-10-04')), 123))->toBe('LM2610-000123');
});
