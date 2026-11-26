<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Data\VariantOptionsText;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Inventory\Persistence\Models\Location;

/**
 * Lưới tồn kho Admin (đọc read model `stock_grid_index`, db-refactor G3): mọi variant (tìm theo tên/mã sản phẩm/SKU, lọc theo giá trị tuỳ chọn — màu, size…, hết hàng / sắp hết theo số bán được
 * online — Σ max(0, tồn − giữ − an toàn) của kho đang giao online, như storefront) × kho. Phân trang theo **sản phẩm**
 * để các variant của một sản phẩm luôn cùng trang (chọn cả sản phẩm, sửa hàng loạt).
 */
final class StockGrid
{
    public const FILTERS = ['all', 'out', 'low'];

    /** Trần an toàn của "mọi kết quả lọc" khi sửa hàng loạt (quá `StockBulkRun::SYNC_LIMIT` thì chạy nền — L20). */
    public const MAX_BULK_VARIANTS = 100000;

    public function __construct(
        private readonly VariantDirectory $variants,
        private readonly StockQueries $queries,
        private readonly LowStockThreshold $lowStock,
    ) {}

    /**
     * @param  array{q?: string, product?: string, filter?: string, options?: array<string, string>, ids?: list<int>|null}  $criteria
     * @return array{products: list<array<string, mixed>>, total_products: int, total_variants: int, locations: list<Location>, option_filters: list<array{code: string, name: string, values: list<string>}>}
     */
    public function page(array $criteria, int $page, int $perPage = 20): array
    {
        $locations = $this->queries->locations();
        $matching = $this->matching($criteria);
        $totals = (clone $matching)->selectRaw('count(*) as variants, count(distinct g.product_id) as products')->first();
        // Phân trang theo sản phẩm (thứ tự tên, mã) — các SKU của một sản phẩm luôn cùng trang.
        $productIds = (clone $matching)->groupBy('g.name_sort', 'g.product_code', 'g.product_id') // cùng thứ tự index stock_grid_sort: đọc theo index, không bảng tạm
            ->orderBy('g.name_sort')->orderBy('g.product_code')->orderBy('g.product_id')
            ->offset(max(0, $page - 1) * $perPage)->limit($perPage)->pluck('g.product_id')->all();
        $pageRows = $productIds === [] ? collect() : (clone $matching)->whereIn('g.product_id', $productIds)
            ->orderBy('g.name_sort')->orderBy('g.product_code')->orderBy('g.product_id')->orderBy('g.variant_rank')->orderBy('g.variant_id')
            ->get(['g.variant_id', 'g.product_code', 'g.status', 'g.tracked', 'g.sellable']);

        // Chỉ phần đang hiển thị mới đọc đủ dữ liệu (tên, SKU) và số liệu từng kho.
        $ids = $pageRows->pluck('variant_id')->map(fn ($id): int => (int) $id)->all();
        $variants = $ids === [] ? [] : $this->variants->find($ids);
        $levels = $ids === [] ? [] : $this->queries->levels($ids);
        $products = [];
        foreach ($pageRows as $row) {
            $variant = $variants[(int) $row->variant_id] ?? null;
            if ($variant === null) {
                continue;
            }
            $code = (string) $row->product_code;
            $products[$code] ??= ['product_code' => $code, 'product_name' => $variant->productName, 'tracked' => $variant->tracksInventory, 'sellable' => 0, 'variants' => []];
            $products[$code]['sellable'] += (int) $row->sellable;
            $products[$code]['variants'][] = [
                'id' => (int) $row->variant_id, 'sku' => $variant->sku, 'product_code' => $code, 'product_name' => $variant->productName,
                'options' => $variant->options, 'options_label' => VariantOptionsText::label($variant->options), 'status' => (string) $row->status, 'sellable' => (int) $row->sellable, 'tracked' => (bool) $row->tracked,
                'levels' => array_map(fn (Location $location): array => $levels[$location->id.':'.$row->variant_id] ?? ['on_hand' => 0, 'reserved' => 0, 'safety_stock' => 0, 'available' => 0], $locations),
            ];
        }

        return [
            'products' => array_values($products),
            'total_products' => (int) ($totals->products ?? 0), 'total_variants' => (int) ($totals->variants ?? 0),
            'locations' => $locations, 'option_filters' => $this->optionFilters($criteria),
        ];
    }

    /**
     * Id mọi variant khớp bộ lọc (chọn "tất cả kết quả" để sửa hàng loạt).
     *
     * @param  array{q?: string, product?: string, filter?: string, options?: array<string, string>, ids?: list<int>|null}  $criteria
     * @return list<int>
     */
    public function variantIds(array $criteria): array
    {
        return $this->matching($criteria)->orderBy('g.variant_id')->limit(self::MAX_BULK_VARIANTS + 1)->pluck('g.variant_id')->map(fn ($id): int => (int) $id)->all();
    }

    /**
     * Tồn bán được (tổng ở kho giao online) theo sản phẩm — cột danh sách sản phẩm Admin (0.3.103).
     *
     * @param  array<int, list<int>>  $variantIdsByProduct  product id => variant id
     * @return array<int, int> product id => số bán được
     */
    public function sellableByProduct(array $variantIdsByProduct): array
    {
        $online = array_values(array_filter($this->queries->locations(), fn (Location $location): bool => $location->status === 'active' && $location->ships_online_orders));
        $all = array_merge([], ...array_values($variantIdsByProduct));
        $levels = $all === [] ? [] : $this->queries->levels($all);
        $result = [];
        foreach ($variantIdsByProduct as $productId => $variantIds) {
            $result[$productId] = 0;
            foreach ($variantIds as $variantId) {
                foreach ($online as $location) {
                    $result[$productId] += max(0, $levels[$location->id.':'.$variantId]['available'] ?? 0);
                }
            }
        }

        return $result;
    }

    /**
     * Số biến thể đang bán (status active) hết hàng / sắp hết ở kho giao online — ô "Việc cần làm" (0.3.101). Sản phẩm không
     * theo dõi tồn luôn bán được nên không tính.
     *
     * @return array{out: int, low: int}
     */
    public function attentionCounts(): array
    {
        $threshold = $this->lowStock->value();
        $row = DB::table('stock_grid_index')->where('status', 'active')->where('tracked', true)
            ->selectRaw('sum(case when sellable = 0 then 1 else 0 end) as out_count, sum(case when sellable > 0 and sellable <= ? then 1 else 0 end) as low_count', [$threshold])
            ->first();

        return ['out' => (int) ($row->out_count ?? 0), 'low' => (int) ($row->low_count ?? 0)];
    }

    /**
     * Truy vấn SKU khớp bộ lọc trên read model `stock_grid_index` (bí danh `g`).
     *
     * @param  array{q?: string, product?: string, filter?: string, options?: array<string, string>, ids?: list<int>|null}  $criteria  `ids`: giới hạn theo bộ lọc của plugin (EP v3 X1)
     */
    private function matching(array $criteria, bool $withOptions = true): Builder
    {
        $product = trim((string) ($criteria['product'] ?? ''));
        $text = mb_strtolower(trim((string) ($criteria['q'] ?? '')));
        $filter = in_array($criteria['filter'] ?? 'all', self::FILTERS, true) ? (string) ($criteria['filter'] ?? 'all') : 'all';
        $threshold = $this->lowStock->value();

        $query = DB::table('stock_grid_index as g')
            ->when($product !== '', fn (Builder $q) => $q->where('g.product_code', $product))
            ->when($product === '' && $text !== '', fn (Builder $q) => $q->where('g.search_text', 'like', '%'.addcslashes($text, '%_\\').'%'))
            ->when(isset($criteria['ids']), fn (Builder $q) => $q->whereIn('g.variant_id', array_map('intval', (array) $criteria['ids'])))
            // Sản phẩm không theo dõi tồn: luôn bán được — không thuộc bộ lọc hết hàng/sắp hết.
            ->when($filter === 'out', fn (Builder $q) => $q->where('g.tracked', true)->where('g.sellable', 0))
            ->when($filter === 'low', fn (Builder $q) => $q->where('g.tracked', true)->where('g.sellable', '>', 0)->where('g.sellable', '<=', $threshold));
        if ($withOptions) {
            foreach (array_filter(array_map(fn ($code): string => trim((string) $code), (array) ($criteria['options'] ?? [])), fn (string $code): bool => $code !== '') as $type => $code) {
                $query->whereExists(fn (Builder $q) => $q->select(DB::raw(1))->from('stock_grid_options as o')
                    ->whereColumn('o.variant_id', 'g.variant_id')->where('o.type_code', (string) $type)->where('o.value_code', $code));
            }
        }

        return $query;
    }

    /**
     * Lựa chọn theo từng loại tuỳ chọn lấy từ kết quả tìm (trước khi lọc tuỳ chọn) để chỉ hiện giá trị có thật.
     *
     * @param  array{q?: string, product?: string, filter?: string, options?: array<string, string>, ids?: list<int>|null}  $criteria
     * @return list<array{code: string, name: string, values: list<string>}>
     */
    private function optionFilters(array $criteria): array
    {
        $found = [];
        $narrowed = trim((string) ($criteria['q'] ?? '')) !== '' || trim((string) ($criteria['product'] ?? '')) !== '' || isset($criteria['ids'])
            || in_array($criteria['filter'] ?? 'all', ['out', 'low'], true);
        // Không lọc gì: đọc thẳng khoá chính (type_code, value_code) — không nối cả bảng SKU.
        foreach (DB::table('stock_grid_options as o')->when($narrowed, fn (Builder $q) => $q->whereIn('o.variant_id', $this->matching($criteria, withOptions: false)->select('g.variant_id')))
            ->distinct()->orderBy('o.type_code')->orderBy('o.value_code')->get(['o.type_code', 'o.value_code']) as $row) {
            $found[(string) $row->type_code][] = (string) $row->value_code;
        }
        $filters = [];
        foreach ($this->variants->optionTypes() as $type => $name) {
            if (isset($found[$type])) {
                $filters[] = ['code' => (string) $type, 'name' => $name, 'values' => $found[$type]];
            }
        }

        return $filters;
    }
}
