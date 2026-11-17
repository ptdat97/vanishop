<?php

declare(strict_types=1);

namespace Modules\Promotion\Contracts\Data;

use Modules\Shared\Contracts\PluginApi;

/**
 * Mã voucher không áp được. reason: not_found | expired | exhausted | not_active | not_applicable.
 */
#[PluginApi]
final readonly class VoucherRejection
{
    public function __construct(
        public string $code,
        public string $reason,
    ) {}
}
