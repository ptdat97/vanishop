<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts;

use Modules\Catalog\Contracts\Data\MediaData;

/**
 * Service contract (0.3.29): plugin dùng ảnh của Thư viện ảnh dùng chung (chọn qua MediaPicker ở Admin) — vd. CMS.
 *
 * Plugin lưu `id` ảnh, tra URL khi hiển thị (`find`), và khai báo nơi đang dùng (`syncUsages`) để Thư viện ảnh không cho
 * xoá ảnh đang dùng. `$ownerType` đặt theo plugin, vd. `plg.cms.post`; Core dùng `catalog.*`.
 */
interface MediaDirectory
{
    /**
     * @param  list<int>  $ids
     * @return array<int, MediaData> id => ảnh (id không tồn tại thì không có mặt)
     */
    public function find(array $ids): array;

    /**
     * Thay toàn bộ ảnh `$owner`/`$role` đang dùng bằng `$mediaIds` (thứ tự giữ nguyên, id không tồn tại bị bỏ qua).
     *
     * @param  list<int>  $mediaIds
     */
    public function syncUsages(string $ownerType, int $ownerId, string $role, array $mediaIds): void;

    /**
     * Bỏ mọi ghi nhận dùng ảnh của `$owner` (khi xoá nội dung); `$ownerId` null = mọi owner loại này (khi gỡ plugin
     * xoá dữ liệu — gọi trong `down()` của migration plugin).
     */
    public function releaseUsages(string $ownerType, ?int $ownerId = null): void;
}
