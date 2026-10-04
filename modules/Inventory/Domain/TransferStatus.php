<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain;

/**
 * Vòng đời chuyển kho (docs/08-inventory/inventory.md §6):
 *   pending → shipped → received;   pending | shipped → cancelled.
 *
 * - `pending`: hàng còn ở kho đi, vẫn bán được (chưa ghi biến động).
 * - `shipped`: hàng đã rời kho đi (movement `transfer_out`); đang đi đường nên KHÔNG bán được.
 * - `received`: hàng đã nhập kho đến (movement `transfer_in`).
 * - `cancelled`: huỷ trước khi gửi (không đổi tồn) hoặc huỷ sau khi gửi (nhập lại kho đi).
 */
enum TransferStatus: string
{
    case Pending = 'pending';
    case Shipped = 'shipped';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Received, self::Cancelled], true);
    }

    /** Hàng đã rời kho đi và đang trên đường (chưa/không tới kho đến). */
    public function isInTransit(): bool
    {
        return $this === self::Shipped;
    }

    public function canMoveTo(self $to): bool
    {
        return match ($this) {
            self::Pending => in_array($to, [self::Shipped, self::Cancelled], true),
            self::Shipped => in_array($to, [self::Received, self::Cancelled], true),
            self::Received, self::Cancelled => false,
        };
    }
}
