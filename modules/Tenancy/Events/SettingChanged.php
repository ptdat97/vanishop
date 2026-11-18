<?php

declare(strict_types=1);

namespace Modules\Tenancy\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Domain event công khai (0.5.29): một cấu hình vừa được ghi hoặc xoá (`Settings::set` / `forget`) — dispatch sau commit.
 * `$value` = giá trị mới, null khi xoá. Module/plugin phản ứng theo cấu hình mà không cần Tenancy biết tới mình (vd. Catalog
 * khai báo ảnh của cấu hình kiểu `media` là "đang dùng" để Thư viện ảnh không cho xoá — backlog L22).
 */
final readonly class SettingChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public string $namespace,
        public string $key,
        public mixed $value,
    ) {}
}
