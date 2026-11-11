<?php

declare(strict_types=1);

namespace Modules\Pricing\Application;

use Modules\Catalog\Contracts\ListPrices;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Pricing\Persistence\Models\Price;
use Modules\Pricing\Persistence\Models\PriceList;

/**
 * Ghi giá niêm yết thấp nhất của sản phẩm sang Catalog (`ListPrices`) để trang danh sách lọc/sắp xếp theo giá.
 * Giá niêm yết = bảng giá `base`, mức mua từ 1 sản phẩm; lấy mức thấp nhất trong các biến thể của sản phẩm (kể cả
 * biến thể ngừng bán — Catalog không báo đổi trạng thái biến thể sang Pricing; lệnh chạy hằng ngày tự sửa lệch).
 */
final class ListPriceSync
{
    public const BASE_CODE = 'base';

    private const CHUNK = 500;

    public function __construct(
        private readonly VariantDirectory $variants,
        private readonly ListPrices $listPrices,
    ) {}

    /**
     * Tính lại cho các sản phẩm chứa những biến thể này.
     *
     * @param  list<int>  $variantIds
     */
    public function forVariants(array $variantIds): void
    {
        foreach (array_chunk(array_values(array_unique($variantIds)), self::CHUNK) as $chunk) {
            $styleIds = array_values(array_unique(array_map(fn ($variant): int => $variant->styleId, $this->variants->find($chunk))));
            $this->forStyles($styleIds);
        }
    }

    /**
     * Tính lại cho mọi sản phẩm có giá niêm yết.
     *
     * @return int số biến thể có giá niêm yết đã xét
     */
    public function all(): int
    {
        $baseId = $this->baseListId();
        if ($baseId === null) {
            return 0;
        }

        $count = 0;
        Price::query()->where('price_list_id', $baseId)->where('min_qty', 1)->select(['id', 'variant_id'])->chunkById(self::CHUNK, function ($prices) use (&$count): void {
            $count += $prices->count();
            $this->forVariants($prices->pluck('variant_id')->map(fn ($id): int => (int) $id)->all());
        });

        return $count;
    }

    /**
     * @param  list<int>  $styleIds
     */
    private function forStyles(array $styleIds): void
    {
        if ($styleIds === []) {
            return;
        }

        $byStyle = $this->variants->ofStyles($styleIds);
        $variantIds = array_merge([], ...array_map(fn (array $variants): array => array_map(fn ($variant): int => $variant->id, $variants), array_values($byStyle)));
        $baseId = $this->baseListId();
        $amounts = $baseId === null || $variantIds === [] ? collect()
            : Price::query()->where('price_list_id', $baseId)->where('min_qty', 1)->whereIn('variant_id', $variantIds)->pluck('amount', 'variant_id');

        $minimums = array_fill_keys($styleIds, null);
        foreach ($byStyle as $styleId => $variants) {
            $found = array_values(array_filter(array_map(fn ($variant): ?int => $amounts->has($variant->id) ? (int) $amounts->get($variant->id) : null, $variants), fn (?int $amount): bool => $amount !== null));
            $minimums[$styleId] = $found === [] ? null : min($found);
        }

        $this->listPrices->setMinimums($minimums);
    }

    private function baseListId(): ?int
    {
        $id = PriceList::query()->where('code', self::BASE_CODE)->value('id');

        return $id === null ? null : (int) $id;
    }
}
