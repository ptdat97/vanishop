<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts;

use Modules\Inventory\Contracts\Data\LocationData;

/**
 * Service contract (0.3.88, ADR-042): danh sách và tạo/sửa kho. Core dùng cho "Kho chính"; plugin `vani.warehouse`
 * quản lý nhiều kho qua contract này — không ghi thẳng bảng tồn. Có audit; sửa dùng optimistic lock (`lock_version`).
 */
interface LocationRegistry
{
    /**
     * @return list<LocationData> theo ưu tiên giảm dần rồi mã
     */
    public function all(): array;

    public function find(int $id): ?LocationData;

    /**
     * Tạo (`$id` null) hoặc sửa kho. Dữ liệu đã validate dạng ở nơi gọi; mã trùng / lock_version cũ → ValidationException.
     *
     * @param  array{code: string, name: string, type: string, address?: ?string, province_code?: ?string, ships_online_orders: bool, allows_pickup: bool, accepts_returns: bool, stock_authority: string, priority: int, status: string}  $data
     */
    public function save(array $data, ?int $id = null, ?int $expectedLockVersion = null): LocationData;
}
