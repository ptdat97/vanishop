<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts;

/**
 * Extension point (tag vani.inventory.strategies): điều chỉnh ATS theo kênh (ví dụ chỉ bán 30% tồn trên sàn).
 * Core luôn lấy min(strategy, chuẩn) — strategy chỉ được GIẢM, không được tăng ATS.
 */
interface InventoryStrategy
{
    /** Tag extension point: plugin đóng góp qua `contribute(InventoryStrategy::TAG, …)`. */
    public const TAG = 'vani.inventory.strategies';

    public function code(): string;

    /**
     * @param  array<int, int>  $standardAts  variant id => ATS chuẩn
     * @return array<int, int>
     */
    public function adjust(array $standardAts, int $channelId): array;
}
