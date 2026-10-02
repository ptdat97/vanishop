<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts\Data;

enum SalesDimension: string
{
    case PaymentMethod = 'payment_method';
    case Source = 'source';
    case Product = 'product';
    case Brand = 'brand';
}
