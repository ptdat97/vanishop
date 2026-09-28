<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts;

use Modules\Ordering\Contracts\Data\OrderData;
use Modules\Ordering\Contracts\Data\OrderLineData;

/**
 * Service contract: đọc đơn (snapshot). Trong phạm vi brand của CurrentContext.
 */
interface OrderReader
{
    public function find(int $orderId): ?OrderData;

    public function findByPublicId(string $publicId): ?OrderData;

    public function findByNumber(string $number): ?OrderData;

    /**
     * @return list<OrderLineData>
     */
    public function lines(int $orderId): array;

    /**
     * Khách đã từng đặt đơn (không tính đơn đã huỷ) chưa — dùng cho rule "đơn đầu tiên".
     *
     * @param  int|null  $brandId  null = bất kỳ brand nào trong phạm vi hiện tại
     */
    public function customerHasPlacedOrder(int $customerId, ?int $brandId = null): bool;
}
