<?php

declare(strict_types=1);

namespace Modules\Channel\Contracts;

/**
 * Service contract: danh sách kênh (cho Admin gán bảng giá, location…).
 */
interface ChannelDirectory
{
    /**
     * Các kênh có bán brand này.
     *
     * @return list<array{id: int, code: string, name: string}>
     */
    public function forBrand(int $brandId): array;
}
