<?php

declare(strict_types=1);

namespace Modules\Returns\Domain;

/**
 * `return_status` của đơn từ các yêu cầu đổi trả: có yêu cầu mở → `requested` (mọi yêu cầu mở còn chờ duyệt) hoặc
 * `in_progress`; không thì theo số lượng đã trả xong so với số đã giao.
 */
final class ReturnProgress
{
    /**
     * @param  list<ReturnStatus>  $statuses  trạng thái mọi yêu cầu của đơn
     */
    public static function forOrder(array $statuses, int $resolvedQuantity, int $deliveredQuantity): string
    {
        $open = array_values(array_filter($statuses, fn (ReturnStatus $status): bool => $status->isOpen()));

        return match (true) {
            $open !== [] => array_filter($open, fn (ReturnStatus $status): bool => $status !== ReturnStatus::Requested) === [] ? 'requested' : 'in_progress',
            $resolvedQuantity === 0 => 'none',
            $resolvedQuantity >= $deliveredQuantity => 'returned',
            default => 'partially_returned',
        };
    }
}
