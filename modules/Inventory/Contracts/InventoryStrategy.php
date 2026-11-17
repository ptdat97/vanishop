<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts;

use Modules\Shared\Contracts\PluginApi;

/**
 * Extension point (tag vani.inventory.strategies): điều chỉnh ATS bán online (ví dụ chừa tồn an toàn theo kho).
 * Core luôn lấy min(strategy, chuẩn) — strategy chỉ được GIẢM, không được tăng ATS.
 */
#[PluginApi]
interface InventoryStrategy
{
    /** Tag extension point: plugin đóng góp qua `contribute(InventoryStrategy::TAG, …)`. */
    public const TAG = 'vani.inventory.strategies';

    public function code(): string;

    /**
     * @param  array<int, int>  $standardAts  variant id => ATS chuẩn
     * @return array<int, int>
     */
    public function adjust(array $standardAts): array;
}
