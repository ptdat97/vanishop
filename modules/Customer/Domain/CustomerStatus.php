<?php

declare(strict_types=1);

namespace Modules\Customer\Domain;

enum CustomerStatus: string
{
    case Active = 'active';
    /** Đã hợp nhất vào khách khác (merged_into_id). */
    case Merged = 'merged';
    /** Đã ẩn danh hoá theo yêu cầu xoá tài khoản; đơn hàng giữ snapshot. */
    case Anonymized = 'anonymized';
}
