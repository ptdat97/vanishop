<?php

declare(strict_types=1);

namespace Modules\Integration\Tests\Feature\Fixtures;

use Modules\Integration\Contracts\Data\DeliveryResult;
use Modules\Integration\Contracts\Data\InboxMessage;
use Modules\Integration\Contracts\InboundHandler;

final class FakeInboundHandler implements InboundHandler
{
    /** @var list<InboxMessage> */
    public static array $handled = [];

    /** @var list<DeliveryResult> */
    public static array $results = [];

    public function system(): string
    {
        return 'fake-shop';
    }

    public function supports(string $messageType): bool
    {
        return $messageType === 'order.status';
    }

    public function handle(InboxMessage $message): DeliveryResult
    {
        self::$handled[] = $message;

        return array_shift(self::$results) ?? DeliveryResult::ok();
    }
}
