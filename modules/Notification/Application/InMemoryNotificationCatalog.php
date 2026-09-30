<?php

declare(strict_types=1);

namespace Modules\Notification\Application;

use Modules\Notification\Contracts\Data\NotificationType;
use Modules\Notification\Contracts\NotificationCatalog;

final class InMemoryNotificationCatalog implements NotificationCatalog
{
    /** @var array<string, NotificationType> */
    private array $types = [];

    public function define(NotificationType $type): void
    {
        $this->types[$type->code] = $type;
    }

    public function types(): array
    {
        return $this->types;
    }
}
