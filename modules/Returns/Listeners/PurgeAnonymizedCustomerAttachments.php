<?php

declare(strict_types=1);

namespace Modules\Returns\Listeners;

use Modules\Customer\Events\CustomerAnonymized;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Returns\Application\ReturnAttachmentPurge;

/**
 * Khách bị ẩn danh hoá (xoá tài khoản) → xoá ngay ảnh đổi trả của mọi đơn của khách (backlog L16).
 */
final class PurgeAnonymizedCustomerAttachments
{
    public function __construct(
        private readonly OrderReader $orders,
        private readonly ReturnAttachmentPurge $purge,
    ) {}

    public function handle(CustomerAnonymized $event): void
    {
        $this->purge->forOrders($this->orders->customerOrderIds($event->customerId));
    }
}
