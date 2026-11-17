<?php

declare(strict_types=1);

namespace Modules\Payment\Domain;

/**
 * Kết quả xét một yêu cầu hoàn: bỏ qua (không còn gì để hoàn), từ chối (kèm lý do + số còn hoàn được) hoặc chấp nhận.
 */
final readonly class RefundDecision
{
    public const SKIP = 'skip';

    public const NOT_COLLECTED = 'not_collected';

    public const EXCEEDS = 'exceeds';

    public const ACCEPT = 'accept';

    private function __construct(public string $outcome, public int $amount = 0, public int $refundable = 0) {}

    public static function skip(): self
    {
        return new self(self::SKIP);
    }

    public static function notCollected(): self
    {
        return new self(self::NOT_COLLECTED);
    }

    public static function exceeds(int $refundable): self
    {
        return new self(self::EXCEEDS, refundable: $refundable);
    }

    public static function accept(int $amount, int $refundable): self
    {
        return new self(self::ACCEPT, $amount, $refundable);
    }
}
