<?php

declare(strict_types=1);

namespace Plugin\SmsBrandname\Infrastructure;

use Illuminate\Support\Str;
use Modules\Customer\Contracts\Data\CustomerContact;
use Modules\Customer\Contracts\Data\OtpPurpose;
use Modules\Customer\Contracts\OtpDeliveryFailed;
use Modules\Customer\Contracts\OtpSender;
use Modules\Shared\Context\CurrentContext;

/**
 * OTP qua SMS brandname. Brandname theo brand của kênh đang gọi (kênh một brand), không thì mặc định.
 */
final class SmsOtpSender implements OtpSender
{
    public function __construct(
        private readonly EsmsClient $client,
        private readonly SmsChannel $channel,
        private readonly CurrentContext $context,
        private readonly string $template,
        private readonly bool $enabled = true,
    ) {}

    public function channel(): string
    {
        return 'sms';
    }

    public function priority(): int
    {
        return 50;
    }

    public function isAvailable(CustomerContact $contact): bool
    {
        return $this->enabled && $this->client->configured();
    }

    public function send(CustomerContact $contact, string $code, OtpPurpose $purpose): void
    {
        $brandIds = $this->context->has() ? $this->context->brandIds() : null;
        $brandId = $brandIds !== null && count($brandIds) === 1 ? $brandIds[0] : null;

        $result = $this->client->send($contact->phone, str_replace('{code}', $code, $this->template), $this->channel->brandnameFor($brandId), 'otp-'.Str::ulid());
        if (! $result->isSent()) {
            throw new OtpDeliveryFailed((string) $result->error);
        }
    }
}
