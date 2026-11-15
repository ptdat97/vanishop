<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Contracts;

use Modules\Fulfillment\Contracts\Data\CarrierAssignmentContext;
use Modules\Fulfillment\Contracts\Data\CarrierChoice;

/**
 * Extension point (tag `vani.fulfillment.carrier_assignments`, EP v3 X3): chọn hãng cho vận đơn mới theo quy tắc của shop
 * (tuyến nội thành/ngoại tỉnh, mức thu hộ, khối lượng…). Core hỏi lần lượt (Core trước, plugin theo thứ tự đăng ký); kết
 * quả đầu tiên trỏ tới hãng đang nhận vận đơn mới thắng; null = để quy tắc mặc định (phương thức khách chọn → hãng mặc
 * định). Nhân viên vẫn huỷ và tạo lại vận đơn với hãng khác trước khi đẩy lên hãng.
 */
interface CarrierAssignment
{
    public const TAG = 'vani.fulfillment.carrier_assignments';

    public function assign(CarrierAssignmentContext $context): ?CarrierChoice;
}
