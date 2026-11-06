<?php

declare(strict_types=1);

namespace Modules\Ordering\Tests\Fixtures;

use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Ordering\Contracts\Data\OrderData;
use Modules\Ordering\Contracts\Data\OrderStatusActionView;
use Modules\Ordering\Contracts\OrderActionRejected;
use Modules\Ordering\Contracts\OrderStatusAction;
use Modules\Ordering\Contracts\OrderTransitions;

/**
 * Plugin thanh toán giả lập (test): "Đối soát với cổng" cho đơn chuyển khoản chưa thanh toán → ghi trạng thái thanh toán qua
 * contract của Core. `mode` điều khiển lỗi để kiểm tra cách Core xử lý.
 */
final class RequeryPaymentStatusAction implements OrderStatusAction
{
    public static string $mode = 'ok';

    /** @var list<array<string, mixed>> */
    public static array $performed = [];

    public function __construct(private readonly OrderTransitions $transitions) {}

    public function code(): string
    {
        return 'test.requery';
    }

    public function describe(OrderData $order): ?OrderStatusActionView
    {
        if ($order->paymentStatus !== 'unpaid') {
            return null;
        }

        return new OrderStatusActionView('Đối soát với cổng', 'payments.confirm', [FieldDefinition::string('reference', 'Mã giao dịch', required: true, max: 40)], primary: true);
    }

    public function perform(OrderData $order, array $input): ?string
    {
        self::$performed[] = $input;
        if (self::$mode === 'business') {
            throw OrderActionRejected::cannotComplete();
        }
        if (self::$mode === 'crash') {
            throw new \RuntimeException('cổng không phản hồi');
        }
        $this->transitions->setPaymentStatus($order->id, 'paid', 'requery:'.$input['reference'], 'plugin');

        return 'Cổng xác nhận đã thanh toán.';
    }
}
