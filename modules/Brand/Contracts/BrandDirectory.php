<?php

declare(strict_types=1);

namespace Modules\Brand\Contracts;

use Modules\Brand\Contracts\Data\BrandData;

interface BrandDirectory
{
    public function find(int $brandId): ?BrandData;

    /**
     * Các brand trong danh sách id (null = tất cả), sắp theo tên.
     *
     * @param  list<int>|null  $brandIds
     * @return list<BrandData>
     */
    public function list(?array $brandIds): array;

    /**
     * @return list<int>
     */
    public function idsOfLegalEntity(int $legalEntityId): array;
}
