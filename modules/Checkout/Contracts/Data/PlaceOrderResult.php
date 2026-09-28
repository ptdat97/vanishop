<?php

declare(strict_types=1);

namespace Modules\Checkout\Contracts\Data;

final readonly class PlaceOrderResult
{
    /**
     * @param  array<string, mixed>  $body  phản hồi đã lưu cho idempotency
     */
    public function __construct(
        public int $status,
        public array $body,
        public bool $replayed,
    ) {}
}
