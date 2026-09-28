<?php

use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Domain\OrderStateMachine;

$allowed = [
    'pending' => ['confirmed', 'cancelled'],
    'confirmed' => ['processing', 'cancelled'],
    'processing' => ['completed', 'cancelled'],
    'completed' => [],
    'cancelled' => [],
];

foreach (OrderStatus::cases() as $from) {
    foreach (OrderStatus::cases() as $to) {
        $expected = in_array($to->value, $allowed[$from->value], true);

        it("{$from->value} → {$to->value} ".($expected ? 'hợp lệ' : 'bị chặn'), function () use ($from, $to, $expected) {
            expect(OrderStateMachine::can($from, $to))->toBe($expected);
        });
    }
}
