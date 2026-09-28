<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts;

use Modules\Shared\Domain\BusinessRuleViolation;

final class PaymentRejected extends BusinessRuleViolation
{
    /**
     * @param  array<string, mixed>  $details
     */
    private function __construct(private readonly string $code_, private readonly int $status_, string $message, private readonly array $details_ = [])
    {
        parent::__construct($message);
    }

    public static function gatewayUnavailable(string $code): self
    {
        return new self('payment.gateway_unavailable', 422, __('payment::messages.gateway_unavailable'), ['gateway' => $code]);
    }

    public static function notFound(): self
    {
        return new self('payment.not_found', 404, __('payment::messages.not_found'));
    }

    public static function invalidState(string $status): self
    {
        return new self('payment.invalid_state', 409, __('payment::messages.invalid_state', ['status' => $status]), ['status' => $status]);
    }

    public static function refundExceeds(int $refundable): self
    {
        return new self('payment.refund_exceeds', 422, __('payment::messages.refund_exceeds', ['amount' => number_format($refundable, 0, ',', '.')]), ['refundable' => $refundable]);
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
