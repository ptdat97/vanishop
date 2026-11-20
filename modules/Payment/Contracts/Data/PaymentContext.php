<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts\Data;

use Modules\Shared\Contracts\PluginApi;
use Modules\Shared\Domain\Money\Money;

/**
 * Dữ liệu để cổng quyết định có khả dụng không ở checkout: số tiền, nơi giao (COD theo vùng), phương thức giao khách chọn
 * (`shippingSource` = mã hãng nếu phí do hãng báo), số món, kênh đặt (web | app | zalo | admin).
 */
#[PluginApi]
final readonly class PaymentContext
{
    public function __construct(
        public Money $amount,
        public ?string $provinceCode = null,
        public ?string $wardCode = null,
        public ?string $shippingMethod = null,
        public ?string $shippingSource = null,
        public int $itemCount = 0,
        public string $channel = 'web',
    ) {}
}
