<?php

use Modules\Fulfillment\Domain\AllocationCheck;
use Modules\Fulfillment\Domain\BookingRetry;
use Modules\Fulfillment\Domain\ShipmentCod;
use Modules\Fulfillment\Domain\ShipmentStatus;

/*
| Domain Fulfillment thuần PHP: đối chiếu phân bổ với hàng đang giữ, COD theo vận đơn,
| ngưỡng đặt lại, trạng thái chờ đặt — không DB.
*/

it('phân bổ phải khớp đúng dòng đơn và không vượt hàng đang giữ ở từng kho', function () {
    $lines = [1 => ['variant' => 10, 'quantity' => 2], 2 => ['variant' => 11, 'quantity' => 1]];
    $reserved = [['location' => 5, 'variant' => 10, 'quantity' => 2], ['location' => 6, 'variant' => 11, 'quantity' => 1]];

    expect(AllocationCheck::matches([['location' => 5, 'lines' => [1 => 2]], ['location' => 6, 'lines' => [2 => 1]]], $lines, $reserved))->toBeTrue()
        ->and(AllocationCheck::matches([['location' => 5, 'lines' => [1 => 1]], ['location' => 6, 'lines' => [2 => 1]]], $lines, $reserved))->toBeFalse()   // thiếu
        ->and(AllocationCheck::matches([['location' => 6, 'lines' => [1 => 2, 2 => 1]]], $lines, $reserved))->toBeFalse()                                // sai kho
        ->and(AllocationCheck::matches([['location' => 5, 'lines' => [1 => 2, 9 => 1]], ['location' => 6, 'lines' => [2 => 1]]], $lines, $reserved))->toBeFalse() // dòng lạ
        ->and(AllocationCheck::matches([['location' => 5, 'lines' => [1 => 2, 2 => 0]], ['location' => 6, 'lines' => [2 => 1]]], $lines, $reserved))->toBeFalse(); // số lượng 0
});

it('COD thu ở vận đơn đầu; đơn trả trước không thu', function () {
    expect(ShipmentCod::amounts(2, true, 330_000))->toBe([330_000, 0])
        ->and(ShipmentCod::amounts(2, false, 330_000))->toBe([0, 0])
        ->and(ShipmentCod::amounts(0, true, 330_000))->toBe([]);
});

it('chờ đặt và hết lượt đặt lại', function () {
    expect(ShipmentStatus::BookingFailed->awaitsBooking())->toBeTrue()
        ->and(ShipmentStatus::Created->awaitsBooking())->toBeFalse()
        ->and(BookingRetry::exhausted(3, 3, ShipmentStatus::PendingBooking))->toBeTrue()
        ->and(BookingRetry::exhausted(2, 3, ShipmentStatus::PendingBooking))->toBeFalse()
        ->and(BookingRetry::exhausted(3, 3, ShipmentStatus::Created))->toBeFalse();   // đã đặt xong giữa chừng
});
