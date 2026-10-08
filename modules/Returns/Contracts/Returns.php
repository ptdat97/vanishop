<?php

declare(strict_types=1);

namespace Modules\Returns\Contracts;

use Modules\Returns\Contracts\Data\ReturnView;

/**
 * Service contract cho Storefront/Admin: tạo yêu cầu đổi/trả, xem theo đơn, khách huỷ yêu cầu chưa xử lý.
 */
interface Returns
{
    /**
     * @param  array<int, int>  $lines  order_line_id => số lượng
     * @param  array<int, int>  $exchanges  đổi hàng (0.3.32): order_line_id => variant thay thế, phải có cho MỌI dòng trả;
     *                                      rỗng = trả hàng hoàn tiền
     *
     * @throws ReturnRejected
     */
    public function request(int $orderId, array $lines, string $reasonCode, ?string $note, string $source, array $exchanges = []): ReturnView;

    /**
     * @return list<ReturnView>
     */
    public function forOrder(int $orderId): array;

    /**
     * Số lượng còn trả được theo dòng (đã giao − đã/đang trả) và hạn trả — để storefront hiển thị form.
     *
     * @return array{lines: array<int, int>, deadline: ?string}
     */
    public function returnable(int $orderId): array;

    public function cancel(int $orderId, string $returnPublicId, string $source): ReturnView;
}
