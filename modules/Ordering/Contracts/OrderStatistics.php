<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts;

use DateTimeImmutable;
use Modules\Ordering\Contracts\Data\SalesBucket;
use Modules\Ordering\Contracts\Data\SalesDimension;
use Modules\Ordering\Contracts\Data\SalesTotals;

/**
 * Service contract (đọc, ADR-030 §4.G): số liệu bán hàng tổng hợp cho báo cáo/dashboard (plugin `vani.reports`).
 * Doanh thu = `total_amount` của đơn **không bị huỷ**, tính theo `placed_at`; khoảng thời gian là [from, to).
 * Ngày được nhóm theo `$timezone` của cửa hàng. Khoảng tối đa {@see self::MAX_DAYS} ngày.
 */
interface OrderStatistics
{
    public const MAX_DAYS = 366;

    public function totals(DateTimeImmutable $from, DateTimeImmutable $to): SalesTotals;

    /**
     * Mỗi ngày trong khoảng một phần tử (kể cả ngày không có đơn), `key` = Y-m-d theo `$timezone`.
     *
     * @return list<SalesBucket>
     */
    public function daily(DateTimeImmutable $from, DateTimeImmutable $to, string $timezone): array;

    /**
     * Nhóm theo một chiều (phương thức thanh toán, kênh, sản phẩm, thương hiệu), doanh thu giảm dần.
     * Chiều theo dòng đơn (`Product`, `Brand`) dùng `order_lines.total_amount` và `quantity`.
     *
     * @return list<SalesBucket>
     */
    public function breakdown(SalesDimension $dimension, DateTimeImmutable $from, DateTimeImmutable $to, int $limit = 20): array;

    /**
     * Sản phẩm (style) bán chạy theo **số lượng** trong khoảng (0.3.43): đơn không huỷ, số lượng dòng sau huỷ một phần (`quantity` đã trừ phần huỷ), không tính
     * đơn thay thế khi đổi hàng; hàng trả lại không trừ; dòng không có id style (đơn cũ) bị bỏ. Số lượng giảm dần, cùng số lượng thì id nhỏ trước. Tối đa 100.
     *
     * @return list<array{style_id: int, quantity: int}>
     */
    public function topStyles(DateTimeImmutable $from, DateTimeImmutable $to, int $limit = 20): array;

    /**
     * Số đơn hiện tại theo trạng thái đơn (`order_status`) — dùng cho "đơn cần xử lý".
     *
     * @return array<string, int>
     */
    public function countByStatus(): array;

    /**
     * Tổng hợp một tập đơn bất kỳ (0.3.36, vd. đơn dùng khuyến mãi của một campaign): doanh thu/giảm giá/phí giao của
     * đơn không huỷ, số đơn huỷ, số khách. Không giới hạn thời gian.
     *
     * @param  list<int>  $orderIds
     */
    public function summarize(array $orderIds): SalesTotals;

    /**
     * Đơn có ít nhất một dòng mua theo một trong các bảng giá (0.3.37, `order_lines.price_list_code`) — vd. đơn hưởng giá
     * sale của campaign.
     *
     * @param  list<string>  $priceListCodes
     * @return list<int>
     */
    public function orderIdsWithPriceLists(array $priceListCodes): array;
}
