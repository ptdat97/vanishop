<?php

use Modules\Inventory\Domain\TransferStatus;

it('chỉ cho các chuyển trạng thái hợp lệ của phiếu chuyển kho', function () {
    expect(TransferStatus::Pending->canMoveTo(TransferStatus::Shipped))->toBeTrue()
        ->and(TransferStatus::Pending->canMoveTo(TransferStatus::Cancelled))->toBeTrue()
        ->and(TransferStatus::Pending->canMoveTo(TransferStatus::Received))->toBeFalse()
        ->and(TransferStatus::Shipped->canMoveTo(TransferStatus::Received))->toBeTrue()
        ->and(TransferStatus::Shipped->canMoveTo(TransferStatus::Cancelled))->toBeTrue()
        ->and(TransferStatus::Shipped->canMoveTo(TransferStatus::Pending))->toBeFalse()
        ->and(TransferStatus::Shipped->isInTransit())->toBeTrue()
        ->and(TransferStatus::Received->isTerminal())->toBeTrue()
        ->and(TransferStatus::Received->canMoveTo(TransferStatus::Cancelled))->toBeFalse()
        ->and(TransferStatus::Cancelled->canMoveTo(TransferStatus::Shipped))->toBeFalse()
        ->and(TransferStatus::Cancelled->isTerminal())->toBeTrue();
});
