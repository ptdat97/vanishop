<?php

declare(strict_types=1);

namespace Modules\Shared\Context;

enum ActorType: string
{
    case Guest = 'guest';
    case Customer = 'customer';
    case Staff = 'staff';
    case Integration = 'integration';
    case System = 'system';
}
