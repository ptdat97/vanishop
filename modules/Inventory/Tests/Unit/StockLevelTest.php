<?php

use Modules\Inventory\Domain\Allocation;
use Modules\Inventory\Domain\InsufficientStock;
use Modules\Inventory\Domain\StockLevel;

it('tính available và ATS', function () {
    $level = new StockLevel(1, 7, onHand: 10, reserved: 3, safetyStock: 2);

    expect($level->available())->toBe(5)->and($level->ats())->toBe(5)
        ->and((new StockLevel(1, 7, onHand: 2, reserved: 3))->available())->toBe(-1)
        ->and((new StockLevel(1, 7, onHand: 2, reserved: 3))->ats())->toBe(0);
});

it('không cho giữ vượt ATS', function () {
    $level = new StockLevel(1, 7, onHand: 5, reserved: 3, safetyStock: 1);

    expect($level->reserve(1)->reserved)->toBe(4);
    expect(fn () => $level->reserve(2))->toThrow(InsufficientStock::class);
});

it('giải phóng và xuất kho', function () {
    $level = new StockLevel(1, 7, onHand: 5, reserved: 3);

    expect($level->release(2)->reserved)->toBe(1)
        ->and($level->commit(2, managesOnHand: true))->toEqual(new StockLevel(1, 7, onHand: 3, reserved: 1))
        ->and($level->commit(2, managesOnHand: false))->toEqual(new StockLevel(1, 7, onHand: 5, reserved: 1));
    expect(fn () => $level->release(4))->toThrow(LogicException::class);
});

it('điều chỉnh không làm tồn âm; số lượng phải dương', function () {
    $level = new StockLevel(1, 7, onHand: 5);

    expect($level->adjust(-5)->onHand)->toBe(0)->and($level->adjust(3)->onHand)->toBe(8);
    expect(fn () => $level->adjust(-6))->toThrow(InvalidArgumentException::class)
        ->and(fn () => $level->reserve(0))->toThrow(InvalidArgumentException::class)
        ->and(fn () => $level->adjust(0))->toThrow(InvalidArgumentException::class);
});

it('đồng bộ số tuyệt đối, bỏ qua bản có version cũ', function () {
    $level = (new StockLevel(1, 7, onHand: 5, syncVersion: 100));

    expect($level->sync(8, 101)->onHand)->toBe(8)
        ->and($level->sync(1, 100))->toBeNull()
        ->and($level->sync(1, 99))->toBeNull()
        ->and($level->sync(2)->onHand)->toBe(2);
});

it('phân bổ theo thứ tự ưu tiên location', function () {
    $levels = [new StockLevel(10, 7, onHand: 2), new StockLevel(20, 7, onHand: 5), new StockLevel(30, 7, onHand: 9)];

    expect(Allocation::allocate(7, 2, $levels))->toBe([10 => 2])
        ->and(Allocation::allocate(7, 4, $levels))->toBe([10 => 2, 20 => 2]);
    expect(fn () => Allocation::allocate(7, 17, $levels))->toThrow(InsufficientStock::class);
});
