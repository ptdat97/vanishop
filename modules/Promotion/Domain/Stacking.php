<?php

declare(strict_types=1);

namespace Modules\Promotion\Domain;

enum Stacking: string
{
    /** Chỉ áp khi chưa có khuyến mãi nào được áp; áp xong thì dừng. */
    case Exclusive = 'exclusive';
    /** Cộng dồn, tính trên số tiền còn lại sau các khuyến mãi trước. */
    case Combinable = 'combinable';
}
