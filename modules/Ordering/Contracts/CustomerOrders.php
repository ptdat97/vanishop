<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts;

use Modules\Ordering\Contracts\Data\OrderDetail;

/**
 * Service contract cho khách (không cần tài khoản):
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

    /**
     * Đơn của khách hàng đã đăng nhập (mới nhất trước).
     *
     * @return array{data: list<OrderDetail>, total: int}
     */
    public function ofCustomer(int $customerId, int $page = 1, int $perPage = 10): array;

    public function showForCustomer(int $customerId, string $publicId): ?OrderDetail;

    /**
     * Khách xác nhận đã nhận hàng (0.3.78, §3.11-1) bằng token đơn; `$waiveReturns` = không đổi trả đơn này.
     *
     * @throws OrderActionRejected
     */
    public function confirmReceived(string $publicId, string $accessToken, bool $waiveReturns = false): OrderDetail;

    /**
     * @throws OrderActionRejected
     */
    public function confirmReceivedForCustomer(int $customerId, string $publicId, bool $waiveReturns = false): OrderDetail;

    /**
     * @throws OrderActionRejected đơn không thuộc khách
     * @throws OrderTransitionRejected nếu đơn không còn huỷ được
     */
    public function cancelForCustomer(int $customerId, string $publicId, string $reason): OrderDetail;
}
