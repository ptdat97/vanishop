<?php

declare(strict_types=1);

namespace Modules\Returns\Contracts;

use Modules\Shared\Domain\BusinessRuleViolation;

final class ReturnRejected extends BusinessRuleViolation
{
    /**
     * @param  array<string, mixed>  $details
     */
    private function __construct(private readonly string $code_, private readonly int $status_, string $message, private readonly array $details_ = [])
    {
        parent::__construct($message);
    }

    public static function notEligible(string $reason): self
    {
        return new self('return.not_eligible', 422, __("returns::messages.policy.{$reason}"), ['reason' => $reason]);
    }

    public static function quantityExceeded(int $orderLineId, int $allowed): self
    {
        return new self('return.quantity_exceeded', 422, __('returns::messages.quantity_exceeded', ['allowed' => $allowed]), ['order_line_id' => $orderLineId, 'allowed' => $allowed]);
    }

    public static function invalidTransition(string $from, string $to): self
    {
        return new self('return.transition_invalid', 409, __('returns::messages.transition_invalid', ['from' => $from, 'to' => $to]), ['from' => $from, 'to' => $to]);
    }

    public static function refundExceeds(int $max): self
    {
        return new self('return.refund_exceeds', 422, __('returns::messages.refund_exceeds'), ['max' => $max]);
    }

    public static function stale(): self
    {
        return new self('return.stale', 409, __('returns::messages.stale'));
    }

    public function errorCode(): string
    {
        return $this->code_;
    }

    public function status(): int
    {
        return $this->status_;
    }

    public function details(): array
    {
        return $this->details_;
    }
}
