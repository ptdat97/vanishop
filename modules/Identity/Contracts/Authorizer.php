<?php

declare(strict_types=1);

namespace Modules\Identity\Contracts;

/**
 * Service contract: kiểm tra quyền của nhân viên (vai trò được gán có permission hoặc `*`). Code thường gọi qua
 * `Gate::allows('<permission>')` — Gate::before của Identity chuyển sang đây cho mọi permission đã đăng ký.
 */
interface Authorizer
{
    public function allows(int $staffUserId, string $permission): bool;
}
