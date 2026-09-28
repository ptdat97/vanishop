<?php

declare(strict_types=1);

namespace Modules\Catalog\Domain;

/**
 * Nhóm màu chuẩn để lọc xuyên brand; tên màu cụ thể do brand đặt.
 */
enum ColorFamily: string
{
    case White = 'white';
    case Black = 'black';
    case Grey = 'grey';
    case Beige = 'beige';
    case Brown = 'brown';
    case Red = 'red';
    case Pink = 'pink';
    case Orange = 'orange';
    case Yellow = 'yellow';
    case Green = 'green';
    case Blue = 'blue';
    case Purple = 'purple';
    case Multi = 'multi';
    case Metallic = 'metallic';
}
