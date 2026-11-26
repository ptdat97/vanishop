<?php

declare(strict_types=1);

namespace Modules\Customer\Application\OtpSenders;

use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Modules\Customer\Contracts\Data\CustomerContact;
use Modules\Customer\Contracts\Data\OtpPurpose;
use Modules\Customer\Contracts\OtpDeliveryFailed;
use Modules\Customer\Contracts\OtpSender;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

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
        try {
            // Thư riêng cho việc xoá tài khoản (L40): khách nhận ra ngay đây không phải mã đăng nhập.
            [$subject, $body] = $purpose === OtpPurpose::DeleteAccount
                ? ['customer::messages.delete_otp_subject', 'customer::messages.delete_otp_body']
                : ['customer::messages.otp_subject', 'customer::messages.otp_body'];
            Mail::raw(__($body, ['code' => $code]), function (Message $message) use ($contact, $subject): void {
                $message->to((string) $contact->email, $contact->fullName)->subject(__($subject));
            });
        } catch (TransportExceptionInterface $exception) {
            // Để Core chuyển sang kênh OTP kế tiếp (SMS/ZNS).
            throw new OtpDeliveryFailed('mail: '.$exception->getMessage(), previous: $exception);
        }
    }
}
