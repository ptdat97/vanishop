<?php

declare(strict_types=1);

namespace Modules\Identity\Contracts;

use Modules\Identity\Contracts\Data\ScopeRef;

interface Authorizer
{
    /**
     * Nhân viên có permission trên phạm vi $target không.
     *
     * $target = null: có permission ở BẤT KỲ phạm vi nào. Thao tác trên một location truyền
     * ScopeRef::location($id): vai trò cấp owner hoặc gán đúng location đó mới qua.
     */
    public function allows(int $staffUserId, string $permission, ?ScopeRef $target = null): bool;
}
