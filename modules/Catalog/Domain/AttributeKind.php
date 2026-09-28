<?php

declare(strict_types=1);

namespace Modules\Catalog\Domain;

/**
 * spec: hiển thị + lọc trên storefront; internal: chỉ dùng vận hành (không lộ ra Storefront API).
 */
enum AttributeKind: string
{
    case Spec = 'spec';
    case Internal = 'internal';
}
