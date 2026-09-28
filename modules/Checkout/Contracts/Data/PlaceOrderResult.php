<?php

declare(strict_types=1);

namespace Modules\Checkout\Contracts\Data;

use Modules\Payment\Contracts\Data\PaymentView;

final readonly class PlaceOrderResult
{
    /**
     * @param  array<string, mixed>  $body  phản hồi đã lưu cho idempotency
     * @param  PaymentView|null  $payment  trạng thái + hành động thanh toán hiện tại (không lưu trong idempotency)
     */
    public function __construct(
        public int $status,
        public array $body,
        public bool $replayed,
        public ?PaymentView $payment = null,
    ) {}
}
