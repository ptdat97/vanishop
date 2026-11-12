<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Dùng kèm `DatabaseTruncation` (test concurrency trên MySQL): giữ các bảng dữ liệu tham chiếu do migration chèn sẵn —
 * truncate xoá chúng thì nghiệp vụ hỏng theo cách không bao giờ xảy ra ở production (vd. `return.reason_invalid`).
 * Thêm bảng vào đây khi một migration mới chèn dữ liệu ban đầu.
 */
trait KeepsReferenceData
{
    /** @var list<string> */
    protected array $exceptTables = ['return_reasons', 'notification_templates'];
}
