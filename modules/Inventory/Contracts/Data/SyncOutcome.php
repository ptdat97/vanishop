<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts\Data;

enum SyncOutcome: string
{
    /** on_hand đổi, đã ghi movement `sync`. */
    case Applied = 'applied';
    /** Version mới nhưng on_hand không đổi — chỉ cập nhật version. */
    case Unchanged = 'unchanged';
    /** Version không mới hơn bản đã nhận — bỏ qua. */
    case Stale = 'stale';
}
