<?php

declare(strict_types=1);

namespace Modules\Customer\Application\OtpSenders;

use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Modules\Customer\Contracts\Data\CustomerContact;
use Modules\Customer\Contracts\Data\OtpPurpose;
use Modules\Customer\Contracts\OtpSender;

/**
 * Kênh OTP mặc định của Core: email đã lưu trên hồ sơ (khách mới chưa có email cần plugin SMS/ZNS).
 */
final class EmailOtpSender implements OtpSender
{
    public function channel(): string
    {
        return 'email';
    }

    public function priority(): int
    {
        return 10;
    }

    public function isAvailable(CustomerContact $contact): bool
    {
        return $contact->email !== null && $contact->email !== '';
    }

    public function send(CustomerContact $contact, string $code, OtpPurpose $purpose): void
    {
        Mail::raw(__('customer::messages.otp_body', ['code' => $code]), function (Message $message) use ($contact): void {
            $message->to((string) $contact->email, $contact->fullName)->subject(__('customer::messages.otp_subject'));
        });
    }
}
