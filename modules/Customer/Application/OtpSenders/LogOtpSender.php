<?php

declare(strict_types=1);

namespace Modules\Customer\Application\OtpSenders;

use Illuminate\Support\Facades\Log;
use Modules\Customer\Contracts\Data\CustomerContact;
use Modules\Customer\Contracts\Data\OtpPurpose;
use Modules\Customer\Contracts\OtpSender;

/**
 * CHỈ cho môi trường dev: ghi mã vào log. Bật bằng `vanishop.customer.otp.log_sender` (mặc định chỉ khi APP_ENV=local).
 */
final class LogOtpSender implements OtpSender
{
    public function __construct(private readonly bool $enabled) {}

    public function channel(): string
    {
        return 'log';
    }

    public function priority(): int
    {
        return 0;
    }

    public function isAvailable(CustomerContact $contact): bool
    {
        return $this->enabled;
    }

    public function send(CustomerContact $contact, string $code, OtpPurpose $purpose): void
    {
        Log::info("[dev] OTP {$purpose->value} cho {$contact->phone}: {$code}");
    }
}
