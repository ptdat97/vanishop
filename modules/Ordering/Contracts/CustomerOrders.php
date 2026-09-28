<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts;

use Modules\Ordering\Contracts\Data\OrderDetail;

/**
 * Service contract cho khách (không cần tài khoản), trong phạm vi brand của kênh:
 * - track: số đơn + SĐT → thông tin rút gọn (địa chỉ/liên hệ bị che)
 * - show/cancel: id đơn + access token trả về lúc đặt
 */
interface CustomerOrders
{
    public function track(string $number, string $phone): ?OrderDetail;

    public function show(string $publicId, string $accessToken): ?OrderDetail;

    /**
     * @throws OrderTransitionRejected nếu đơn không còn huỷ được (đã xử lý/giao)
     */
    public function cancel(string $publicId, string $accessToken, string $reason): OrderDetail;
}
