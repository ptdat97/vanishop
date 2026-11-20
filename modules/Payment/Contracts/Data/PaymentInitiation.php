<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts\Data;

use Modules\Shared\Contracts\PluginApi;

/**
 * Việc khách cần làm tiếp: none (COD), redirect (trang cổng), qr (mã QR), instructions (chuyển khoản thủ công).
 * Hằng `NONE`/`REDIRECT`/`QR`/`INSTRUCTIONS` là viết tắt của các case `InitiationType`.
 */
#[PluginApi]
final readonly class PaymentInitiation
{
    public const NONE = InitiationType::None;

    public const REDIRECT = InitiationType::Redirect;

    public const QR = InitiationType::Qr;

    public const INSTRUCTIONS = InitiationType::Instructions;

    /**
     * @param  array<string, string>  $instructions
     */
    public function __construct(
        public InitiationType $type,
        public ?string $url = null,
        public ?string $qrPayload = null,
        public array $instructions = [],
        public ?string $gatewayReference = null,
    ) {}

    public static function none(): self
    {
        return new self(InitiationType::None);
    }
}
