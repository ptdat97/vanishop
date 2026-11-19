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
        return new self('idempotency.conflict', __('shared::texts.idempotency_conflict.idempotency_key_was_already_used'));
    }

    public static function inProgress(): self
    {
        return new self('idempotency.in_progress', __('shared::texts.idempotency_conflict.request_idempotency_key_being_processed'));
    }

    public function errorCode(): string
    {
        return $this->code_;
    }
}
