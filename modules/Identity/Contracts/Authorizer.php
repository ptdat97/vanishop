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

    /**
     * Super admin: nhân viên có vai trò toàn quyền (`*`, vai trò hệ thống `owner_admin`; giao diện không cấp được `*`).
     * Dùng cho thao tác chỉ chủ cửa hàng được làm, vd. đổi đường dẫn Admin. Gate: `identity.super_admin`.
     */
    public function isSuperAdmin(int $staffUserId): bool;
}
