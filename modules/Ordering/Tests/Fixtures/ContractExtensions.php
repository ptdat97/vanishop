<?php

declare(strict_types=1);

namespace Modules\Ordering\Tests\Fixtures;

use Modules\Ordering\Contracts\ConfirmationChannel;
use Modules\Ordering\Contracts\Data\OrderData;
use Modules\Ordering\Contracts\PackingSlipTemplate;

/** Kênh xác nhận đơn mẫu (chạy ConfirmationChannelContract). */
final class LogConfirmationChannel implements ConfirmationChannel
{
    public function code(): string
    {
        return 'test-log';
    }

    public function label(): string
    {
        return 'Ghi log (thử)';
    }

    public function request(OrderData $order): string
    {
        return "Đã ghi yêu cầu xác nhận đơn {$order->number}.";
    }
}

/** Mẫu phiếu giao mẫu dùng view của Core (chạy PackingSlipTemplateContract). */
final class CoreViewSlipTemplate implements PackingSlipTemplate
{
    public function code(): string
    {
        return 'test-a5';
    }

    public function label(): string
    {
        return 'A5 (thử)';
    }

    public function view(): string
    {
        return 'ordering::admin.print';
    }
}
