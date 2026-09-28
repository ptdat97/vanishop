<?php

declare(strict_types=1);

namespace Modules\Identity\Contracts;

use Modules\Identity\Contracts\Data\ScopeRef;

interface Authorizer
{
    /**
     * Nhân viên có permission trên phạm vi $target không.
     *
     * $target = null: có permission ở BẤT KỲ phạm vi nào (dùng cho truy cập trang/menu; dữ liệu cụ thể
     * vẫn bị lọc theo phạm vi). Thao tác cấp Owner phải truyền ScopeRef::owner() tường minh.
     */
    public function allows(int $staffUserId, string $permission, ?ScopeRef $target = null): bool;

    /**
     * Các brand nhân viên được thấy; null = không giới hạn (có vai trò cấp Owner).
     *
     * @return list<int>|null
     */
    public function accessibleBrandIds(int $staffUserId): ?array;
}
