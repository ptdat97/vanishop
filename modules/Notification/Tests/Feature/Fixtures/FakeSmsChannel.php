<?php

declare(strict_types=1);

namespace Modules\Notification\Tests\Feature\Fixtures;

use Modules\Notification\Contracts\Data\OutgoingMessage;
use Modules\Notification\Contracts\Data\Recipient;
use Modules\Notification\Contracts\Data\SendResult;
use Modules\Notification\Contracts\NotificationChannel;

final class FakeSmsChannel implements NotificationChannel
{
    /** @var list<OutgoingMessage> */
    public static array $sent = [];

    /** @var list<SendResult> */
    public static array $results = [];

    public function code(): string
    {
        return 'sms';
    }

    public function canReach(Recipient $recipient): bool
    {
        return $recipient->phone !== null;
    }

    public function send(OutgoingMessage $message): SendResult
    {
        self::$sent[] = $message;

        return array_shift(self::$results) ?? SendResult::sent('SMS-'.$message->logId);
    }
}
