<?php

declare(strict_types=1);

namespace Modules\Integration\Contracts;

use Modules\Integration\Contracts\Data\IntegrationEvent;

/**
 * Service contract: phát một event tích hợp — ghi vào event feed (`GET /events`) và fan-out thành message
 * outbox cho webhook subscription + connector đăng ký loại event đó, trong cùng một transaction.
 */
interface IntegrationEvents
{
    /**
     * @return string event_id (uuid)
     */
    public function publish(IntegrationEvent $event): string;
}
