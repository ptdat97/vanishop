<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts\Data;

use Modules\Shared\Contracts\PluginApi;

/**
 * Kết quả lệnh hoàn tiền qua cổng (`RefundsPayments`, `TracksRefunds`).
 */
#[PluginApi]
final readonly class RefundResult
{
    public function __construct(
        public RefundOutcome $outcome,
        public ?string $gatewayReference = null,
        public ?string $message = null,
    ) {}

    public static function succeeded(?string $gatewayReference = null): self
    {
        return new self(RefundOutcome::Succeeded, $gatewayReference);
    }

    public static function pending(?string $gatewayReference = null, ?string $message = null): self
    {
        return new self(RefundOutcome::Pending, $gatewayReference, $message);
    }

    public static function failed(?string $message = null): self
    {
        return new self(RefundOutcome::Failed, null, $message);
    }
}
