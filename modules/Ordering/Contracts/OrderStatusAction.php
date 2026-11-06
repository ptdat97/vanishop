<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts;

use Modules\Ordering\Contracts\Data\OrderData;
use Modules\Ordering\Contracts\Data\OrderStatusActionView;

/**
 * Extension point (0.3.98): thao tác đổi trạng thái do plugin (thanh toán, vận chuyển…) cắm vào thẻ "Trạng thái đơn" trên
 * trang đơn Admin — vd. cổng thanh toán "Đối soát lại với cổng", hãng vận chuyển "Đồng bộ trạng thái giao", "Đánh dấu thu
 * hộ đã về". Core hiển thị nút, kiểm tra quyền (`OrderStatusActionView::$permission`), validate trường nhập, gọi `perform()`
 * rồi ghi audit `order.status_action`.
 *
 * `perform()` chỉ đổi trạng thái qua contract của Core (OrderTransitions, Payments/Shipments…) — không ghi thẳng bảng của
 * Core (R30). Lỗi nghiệp vụ: ném `BusinessRuleViolation` (hiện cho nhân viên); lỗi khác được ghi log và báo chung.
 *
 * Đăng ký: `$this->contribute(OrderStatusAction::TAG, MyAction::class)` trong PluginServiceProvider.
 */
interface OrderStatusAction
{
    public const TAG = 'vani.ordering.status_actions';

    /** Mã ổn định, duy nhất (a-z0-9 . _ -), vd. `vani.vnpay.requery`. */
    public function code(): string;

    /** Nút cho đơn này; null = không áp dụng (sai trạng thái, không phải cổng/hãng của plugin…). */
    public function describe(OrderData $order): ?OrderStatusActionView;

    /**
     * Thực hiện. `$input`: giá trị đã validate theo `fields` của describe().
     *
     * @param  array<string, mixed>  $input
     * @return string|null thông báo thành công (null = câu mặc định)
     */
    public function perform(OrderData $order, array $input): ?string;
}
