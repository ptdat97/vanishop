<?php

declare(strict_types=1);

namespace Modules\Identity\Contracts;

/**
 * Service contract: tên nhân viên theo id — để màn hình Admin của module khác hiện "ai đã làm" (lịch sử giá…) thay cho mã.
 */
interface StaffNames
{
    /**
     * @param  list<int>  $staffIds
     * @return array<int, string> id => tên (nhân viên đã xoá thì vắng)
     */
    public function names(array $staffIds): array;
}
