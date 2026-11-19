<?php

declare(strict_types=1);

namespace Modules\Catalog\Application;

use RuntimeException;

/**
 * Optimistic lock: bản ghi đã bị người khác sửa sau khi form được mở.
 */
final class StaleRecord extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('catalog::texts.stale_record.data_was_updated_someone_else'));
    }
}
