<?php

declare(strict_types=1);

namespace Modules\Checkout\Contracts\Data;

use Modules\Shared\Domain\Money\Money;

/**
 * Điều chỉnh truy vết được: giảm giá (âm), phí (dương). `source` = "core" hoặc mã plugin.
 */
final readonly class Adjustment
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public string $type,
        public string $source,
        public ?string $code,
        public string $label,
        public Money $amount,
        public array $meta = [],
    ) {}
}
