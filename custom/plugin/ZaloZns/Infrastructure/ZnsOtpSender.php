<?php

declare(strict_types=1);

namespace Plugin\ZaloZns\Infrastructure;

use Illuminate\Support\Str;
use Modules\Customer\Contracts\Data\CustomerContact;
use Modules\Customer\Contracts\Data\OtpPurpose;
use Modules\Customer\Contracts\OtpDeliveryFailed;
use Modules\Customer\Contracts\OtpSender;

/**
 * OTP qua ZNS (rẻ hơn SMS nên priority cao hơn). SĐT không dùng Zalo → OtpDeliveryFailed, Core chuyển sang SMS.
 */
final class ZnsOtpSender implements OtpSender
{
    public function __construct(
        private readonly ZnsClient $client,
        private readonly string $templateId,
        private readonly string $param = 'otp',
    ) {}

    public function channel(): string
    {
        return 'zns';
    }

    public function priority(): int
    {
        return 60;
    }

    public function isAvailable(CustomerContact $contact): bool
    {
        return $this->templateId !== '' && $this->client->configured();
    }

    public function send(CustomerContact $contact, string $code, OtpPurpose $purpose): void
    {
        $result = $this->client->send($contact->phone, $this->templateId, [$this->param => $code], 'otp-'.Str::ulid());
        if (! $result->isSent()) {
            throw new OtpDeliveryFailed((string) $result->error);
        }
    }
}
