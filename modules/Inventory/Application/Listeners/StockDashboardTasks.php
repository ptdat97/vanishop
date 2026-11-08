<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Listeners;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Modules\Inventory\Application\StockGrid;

/**
 * Ô "Việc cần làm" của tồn kho (0.3.101): biến thể đang bán đã hết hàng / sắp hết (≤ ngưỡng) ở kho giao online. Tính trên
 * lưới tồn (tối đa 5000 biến thể, backlog L20) nên nhớ 5 phút.
 */
final class StockDashboardTasks
{
    public const CACHE_KEY = 'vani:dashboard:stock-counts';

    public function __construct(private readonly StockGrid $grid) {}

    /**
     * @return list<array{key: string, label: string, count: int, url: string, tone: string, hint: string, order: int}>
     */
    public function __invoke(): array
    {
        if (! Gate::allows('inventory.view')) {
            return [];
        }
        $counts = Cache::remember(self::CACHE_KEY, 300, fn (): array => $this->grid->attentionCounts());
        $url = fn (string $filter): string => route('admin.inventory.stock.index', ['filter' => $filter]);

        return [
            ['key' => 'stock.out', 'label' => 'Hết hàng', 'count' => $counts['out'], 'url' => $url('out'), 'tone' => 'danger', 'hint' => 'Biến thể đang bán · cập nhật 5 phút/lần', 'order' => 70],
            ['key' => 'stock.low', 'label' => 'Sắp hết hàng', 'count' => $counts['low'], 'url' => $url('low'), 'tone' => 'warning', 'hint' => 'Còn ≤ '.(int) config('vanishop.inventory.low_stock_threshold', 3).' sản phẩm bán được', 'order' => 71],
        ];
    }
}
