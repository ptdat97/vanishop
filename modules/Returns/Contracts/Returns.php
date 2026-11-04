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

    /**
     * Lý do đổi trả đang bật (0.3.74, Admin → Đổi trả → Lý do), theo thứ tự hiển thị — cho form của khách.
     *
     * @return list<array{code: string, label: string, store_fault: bool}>
     */
    public function reasons(string $locale): array;

    /**
     * Tên lý do theo ngôn ngữ (cả lý do đã tắt); mã lạ → chính mã.
     */
    public function reasonLabel(string $code, string $locale): string;

    /**
     * Khách báo đã gửi hàng về (0.3.74): yêu cầu đã duyệt → đang gửi về, kèm mã vận đơn trả (tuỳ chọn).
     *
     * @throws ReturnRejected trạng thái khác "đã duyệt"
     */
    public function markShipped(int $orderId, string $returnPublicId, ?string $trackingNumber, string $source): ReturnView;
}
