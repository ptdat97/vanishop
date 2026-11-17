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

    public static function socialUnknown(): self
    {
        return new self(__('customer::messages.social_unknown'), 'customer.social_unknown', 404);
    }

    public static function socialState(): self
    {
        return new self(__('customer::messages.social_state'), 'customer.social_state', 422);
    }

    public static function socialRedirect(): self
    {
        return new self(__('customer::messages.social_redirect'), 'customer.social_redirect', 422);
    }

    public static function socialFailed(): self
    {
        return new self(__('customer::messages.social_failed'), 'customer.social_failed', 422);
    }

    /** Nhà cung cấp không trả email (hoặc SĐT) đã xác minh để ghép/tạo tài khoản. */
    public static function socialEmailRequired(): self
    {
        return new self(__('customer::messages.social_email_required'), 'customer.social_email_required', 422);
    }

    /** Link xác minh email đăng ký sai hoặc hết hạn. */
    public static function registrationLinkInvalid(): self
    {
        return new self(__('customer::messages.registration_link_invalid'), 'customer.registration_link_invalid', 422);
    }

    /** Link đặt lại mật khẩu sai hoặc hết hạn. */
    public static function passwordResetInvalid(): self
    {
        return new self(__('customer::messages.password_reset_invalid'), 'customer.password_reset_invalid', 422);
    }

    /** Thao tác cần nhập lại mật khẩu nhưng tài khoản chưa có (đăng nhập OTP/mạng xã hội): đặt mật khẩu trước. */
    public static function passwordRequired(): self
    {
        return new self(__('customer::messages.password_required'), 'customer.password_required', 422);
    }

    public static function tooManyAttempts(int $retryAfter): self
    {
        return new self(__('customer::messages.too_many_attempts'), 'customer.too_many_attempts', 429, ['retry_after' => $retryAfter]);
    }

    public static function emailTaken(): self
    {
        return new self(__('customer::messages.email_taken'), 'customer.email_taken', 409);
    }

    public static function addressLimit(int $max): self
    {
        return new self(__('customer::messages.address_limit', ['max' => $max]), 'customer.address_limit', 422, ['max' => $max]);
    }

    public static function phoneInvalid(): self
    {
        return new self(__('customer::messages.phone_invalid'), 'customer.phone_invalid', 422);
    }

    public static function addressInvalid(): self
    {
        return new self(__('customer::messages.address_invalid'), 'customer.address_invalid', 422);
    }

    public static function notFound(): self
    {
        return new self(__('customer::messages.not_found'), 'customer.not_found', 404);
    }

    public static function mergeInvalid(string $reason): self
    {
        return new self($reason, 'customer.merge_invalid', 422);
    }

    /**
     * Khách tự xoá tài khoản khi còn việc dở (đơn chưa hoàn tất, đổi trả đang mở…).
     *
     * @param  list<array{type: string, reference: string, label: string}>  $blockers
     */
    public static function deletionBlocked(array $blockers): self
    {
        return new self(
            __('customer::messages.deletion_blocked', ['items' => implode(', ', array_map(fn (array $blocker): string => $blocker['label'], $blockers))]),
            'customer.deletion_blocked',
            409,
            ['blockers' => $blockers],
        );
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
