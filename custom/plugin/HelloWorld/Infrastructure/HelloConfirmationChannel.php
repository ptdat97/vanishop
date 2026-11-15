<?php

declare(strict_types=1);

namespace Plugin\HelloWorld\Infrastructure;

use Modules\Ordering\Contracts\ConfirmationChannel;
use Modules\Ordering\Contracts\Data\OrderData;

/**
 * Kênh xác nhận đơn mẫu (EP v3 X2 — tham chiếu cho `ConfirmationChannel`): không gửi đi đâu, chỉ trả thông báo. Plugin thật
 * (Zalo OA, tổng đài) gửi tin rồi nhận phản hồi qua webhook của mình và gọi `OrderActions::confirm()`/`cancel()`.
 */
final class HelloConfirmationChannel implements ConfirmationChannel
{
    public function code(): string
    {
        return 'hello';
    }

    public function label(): string
    {
        return 'Hello (thử)';
    }

    public function request(OrderData $order): string
    {
        return "Đã gửi lời chào xác nhận đơn {$order->number}.";
    }
}
