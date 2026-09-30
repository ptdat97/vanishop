<?php

declare(strict_types=1);

namespace Modules\Customer\Contracts;

use Modules\Shared\Domain\BusinessRuleViolation;

final class CustomerRejected extends BusinessRuleViolation
{
    /**
     * @param  array<string, mixed>  $details
     */
    private function __construct(string $message, private readonly string $errorCode, private readonly int $httpStatus, private readonly array $details = [])
    {
        parent::__construct($message);
    }

    public static function unauthenticated(): self
    {
        return new self(__('customer::messages.unauthenticated'), 'customer.unauthenticated', 401);
    }

    public static function otpInvalid(): self
    {
        return new self(__('customer::messages.otp_invalid'), 'customer.otp_invalid', 422);
    }

    public static function otpRateLimited(int $retryAfter): self
    {
        return new self(__('customer::messages.otp_rate_limited'), 'customer.otp_rate_limited', 429, ['retry_after' => $retryAfter]);
    }

    public static function otpUnavailable(): self
    {
        return new self(__('customer::messages.otp_unavailable'), 'customer.otp_unavailable', 422);
    }

    public static function invalidCredentials(): self
    {
        return new self(__('customer::messages.invalid_credentials'), 'customer.invalid_credentials', 401);
    }

    public static function emailTaken(): self
    {
        return new self(__('customer::messages.email_taken'), 'customer.email_taken', 409);
    }

    public static function addressLimit(int $max): self
    {
        return new self(__('customer::messages.address_limit', ['max' => $max]), 'customer.address_limit', 422, ['max' => $max]);
    }

    public static function notFound(): self
    {
        return new self(__('customer::messages.not_found'), 'customer.not_found', 404);
    }

    public static function mergeInvalid(string $reason): self
    {
        return new self($reason, 'customer.merge_invalid', 422);
    }

    public static function passwordMismatch(): self
    {
        return new self(__('customer::messages.password_mismatch'), 'customer.password_mismatch', 422);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function status(): int
    {
        return $this->httpStatus;
    }

    public function details(): array
    {
        return $this->details;
    }
}
