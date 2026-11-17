<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts\Data;

use Modules\Shared\Contracts\PluginApi;

#[PluginApi]
enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
