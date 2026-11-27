<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts;

/**
 * Service contract: khung giờ hiển thị sản phẩm (`published_from` / `published_to`). Cache trang (ADR-051) dùng mốc sớm
 * nhất làm hạn của bản cache để sản phẩm hẹn giờ hiện/ẩn đúng lúc.
 */
interface PublicationSchedule
{
    /** Mốc hiện/ẩn sớm nhất sau `$now` của sản phẩm đang bán (Unix timestamp), null nếu không có. */
    public function nextChangeAfter(int $now): ?int;
}
