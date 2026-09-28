<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts\Data;

/**
 * Việc khách cần làm tiếp: none (COD), redirect (trang cổng), qr (mã QR), instructions (chuyển khoản thủ công).
 */
final readonly class PaymentInitiation
{
    public const NONE = 'none';

    public const REDIRECT = 'redirect';

    public const QR = 'qr';

    public const INSTRUCTIONS = 'instructions';

    /**
     * @param  array<string, string>  $instructions
     */
    public function __construct(
        public string $type,
        public ?string $url = null,
        public ?string $qrPayload = null,
        public array $instructions = [],
        public ?string $gatewayReference = null,
    ) {}

    public static function none(): self
    {
        return new self(self::NONE);
    }
}
