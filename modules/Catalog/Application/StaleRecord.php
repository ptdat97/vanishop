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
        parent::__construct('Dữ liệu đã được người khác cập nhật. Vui lòng tải lại trang rồi sửa lại.');
    }
}
