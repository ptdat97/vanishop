<?php

declare(strict_types=1);

namespace Modules\Checkout\Contracts\Data;

/**
 * Lỗi chặn đặt hàng. `field` theo tên trường API (vd. "contact.phone"), null = cả đơn.
 */
final readonly class CheckoutIssue
{
    public function __construct(
        public string $code,
        public string $message,
        public ?string $field = null,
    ) {}
}
