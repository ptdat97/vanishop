<?php

declare(strict_types=1);

namespace Modules\Customer\Contracts;

use Modules\Shared\Contracts\PluginApi;

/**
 * Service contract (EP v3 X6): ghi chú CSKH theo khách — nhân viên và plugin (tổng đài ghi cuộc gọi, chống bom hàng ghi
 * lý do gắn nhãn…). Append-only; hiện ở trang khách và thẻ "Ghi chú CSKH" trên trang đơn. `source`: `staff` |
 * `system:<id plugin>`.
 */
#[PluginApi(PluginApi::EXPERIMENTAL)]
interface CustomerNotes
{
    public function add(int $customerId, string $note, string $source): void;

    /**
     * @return list<array{note: string, source: string, at: string|null}>
     */
    public function latest(int $customerId, int $limit = 20): array;
}
