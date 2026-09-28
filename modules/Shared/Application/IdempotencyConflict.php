<?php

declare(strict_types=1);

namespace Modules\Shared\Application;

use Modules\Shared\Domain\BusinessRuleViolation;

final class IdempotencyConflict extends BusinessRuleViolation
{
    private function __construct(private readonly string $code_, string $message)
    {
        parent::__construct($message);
    }

    public static function differentRequest(): self
    {
        return new self('idempotency.conflict', 'Idempotency-Key đã được dùng cho một yêu cầu khác.');
    }

    public static function inProgress(): self
    {
        return new self('idempotency.in_progress', 'Yêu cầu với Idempotency-Key này đang được xử lý. Vui lòng thử lại sau.');
    }

    public function errorCode(): string
    {
        return $this->code_;
    }
}
