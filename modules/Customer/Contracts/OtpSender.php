<?php

declare(strict_types=1);

namespace Modules\Customer\Contracts;

use Modules\Customer\Contracts\Data\CustomerContact;
use Modules\Customer\Contracts\Data\OtpPurpose;

/**
 * Extension point: kênh gửi OTP. Core có `email` (khi khách có email) và `log` (chỉ dev); SMS brandname /
 * Zalo ZNS là plugin (`vani.sms-brandname`, `vani.zalo-zns`). Kênh đầu tiên `isAvailable()` theo `priority()`
 * cao nhất được dùng.
 */
interface OtpSender
{
    public const TAG = 'vani.customer.otp_senders';

    public function channel(): string;

    /** Số lớn hơn được thử trước (SMS/ZNS nên cao hơn email). */
    public function priority(): int;

    public function isAvailable(CustomerContact $contact): bool;

    public function send(CustomerContact $contact, string $code, OtpPurpose $purpose): void;
}
