<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts;

use Modules\Shared\Domain\BusinessRuleViolation;

final class OrderActionRejected extends BusinessRuleViolation
{
    private function __construct(private readonly string $code_, private readonly int $status_, string $message)
    {
        parent::__construct($message);
    }

    public static function cannotCancel(): self
    {
        return new self('order.cannot_cancel', 409, __('ordering::messages.cannot_cancel'));
    }

    public static function cannotChangeAddress(): self
    {
        return new self('order.cannot_change_address', 409, __('ordering::messages.cannot_change_address'));
    }

    public static function stale(): self
    {
        return new self('order.stale', 409, __('ordering::messages.stale'));
    }

    public static function notFound(): self
    {
        return new self('order.not_found', 404, __('ordering::messages.not_found'));
    }

    public function errorCode(): string
    {
        return $this->code_;
    }

    public function status(): int
    {
        return $this->status_;
    }
}
