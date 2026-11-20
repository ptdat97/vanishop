<?php

declare(strict_types=1);

namespace Modules\Promotion\Contracts\Data;

/**
 * Mã voucher không áp được. reason: not_found | expired | exhausted | not_active | not_applicable.
 */
final readonly class VoucherRejection
{
    public function __construct(
        public string $code,
        public string $reason,
    ) {}
}
