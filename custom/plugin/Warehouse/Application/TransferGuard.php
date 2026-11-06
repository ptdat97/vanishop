<?php

declare(strict_types=1);

namespace Plugin\Warehouse\Application;

use Plugin\Warehouse\Domain\TransferStatus;
use Plugin\Warehouse\Persistence\Transfer;

/**
 * Chặn tắt vani.warehouse khi còn phiếu chuyển đang đi đường (`shipped`): tắt giữa chừng thì không nhận/huỷ được, hàng
 * đã trừ kho đi nhưng chưa vào kho đến.
 */
final class TransferGuard
{
    public const TAG = 'vani.warehouse.transfer_guard';

    public function blocker(): ?string
    {
        $inTransit = Transfer::query()->where('status', TransferStatus::Shipped)->count();

        return $inTransit === 0 ? null : __('vani-warehouse::messages.transfers_in_transit', ['count' => $inTransit]);
    }
}
