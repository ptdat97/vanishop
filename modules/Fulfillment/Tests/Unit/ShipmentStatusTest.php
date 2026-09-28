<?php

use Modules\Fulfillment\Domain\FulfillmentProgress;
use Modules\Fulfillment\Domain\ShipmentStatus as S;

it('trạng thái vận đơn chỉ đi tiến; lặp giao lại được; không huỷ sau khi rời kho', function () {
    expect(S::Created->canMoveTo(S::PickedUp))->toBeTrue()
        ->and(S::InTransit->canMoveTo(S::PickedUp))->toBeFalse()
        ->and(S::OutForDelivery->canMoveTo(S::FailedAttempt))->toBeTrue()
        ->and(S::FailedAttempt->canMoveTo(S::OutForDelivery))->toBeTrue()
        ->and(S::Delivered->canMoveTo(S::InTransit))->toBeFalse()
        ->and(S::Created->canMoveTo(S::Cancelled))->toBeTrue()
        ->and(S::PickedUp->canMoveTo(S::Cancelled))->toBeFalse()
        ->and(S::Created->canMoveTo(S::Returned))->toBeFalse()
        ->and(S::Returning->canMoveTo(S::Returned))->toBeTrue()
        ->and(S::Returning->canMoveTo(S::Delivered))->toBeFalse()
        ->and(S::PendingBooking->canMoveTo(S::BookingFailed))->toBeTrue()
        ->and(S::BookingFailed->canMoveTo(S::Created))->toBeTrue();
});

it('tổng hợp fulfillment_status của đơn từ các vận đơn', function (array $statuses, string $expected, bool $left) {
    expect(FulfillmentProgress::orderStatus($statuses))->toBe($expected)
        ->and(FulfillmentProgress::allLeftWarehouse($statuses))->toBe($left);
})->with([
    'chưa có' => [[], 'unfulfilled', false],
    'đã huỷ hết' => [[S::Cancelled], 'unfulfilled', false],
    'chờ đặt' => [[S::PendingBooking], 'allocated', false],
    'một phần' => [[S::PickedUp, S::Created], 'partially_shipped', false],
    'đã xuất hết' => [[S::InTransit, S::Delivered, S::Cancelled], 'shipped', true],
    'giao hết' => [[S::Delivered, S::Delivered], 'delivered', true],
    'hoàn hết' => [[S::Returned], 'returned_to_sender', true],
]);
