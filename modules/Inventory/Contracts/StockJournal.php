<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts;

use Modules\Shared\Contracts\PluginApi;

/** Đọc sổ biến động theo biến thể, mới nhất trước. */
#[PluginApi]
interface StockJournal
{
    public const MAX_ENTRIES = 1000;

    /**
     * @return list<array{id: int, at: ?string, location: string, type: string, on_hand_delta: int, reserved_delta: int, on_hand_after: int, reserved_after: int, reason: ?string, reference: ?string, actor: ?string}>
     */
    public function movements(int $variantId, int $limit = 100, ?int $locationId = null): array;
}
