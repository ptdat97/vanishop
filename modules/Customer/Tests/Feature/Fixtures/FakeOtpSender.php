<?php

declare(strict_types=1);

namespace Modules\Customer\Tests\Feature\Fixtures;

use Modules\Customer\Contracts\Data\CustomerContact;
use Modules\Customer\Contracts\Data\OtpPurpose;
use Modules\Customer\Contracts\OtpSender;

final class FakeOtpSender implements OtpSender
{
    /** @var array<string, string> phone|purpose => code gần nhất */
    public static array $codes = [];

    public static bool $available = true;

    public function channel(): string
    {
        return 'sms';
    }

    public function priority(): int
    {
        return 100;
    }

    public function isAvailable(CustomerContact $contact): bool
    {
        return self::$available;
    }

    public function send(CustomerContact $contact, string $code, OtpPurpose $purpose): void
    {
        self::$codes[$contact->phone.'|'.$purpose->value] = $code;
    }
}
