<?php

declare(strict_types=1);

namespace Plugin\Bundles\Infrastructure;

use Modules\Catalog\Contracts\MediaDirectory;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Inventory\Contracts\AvailabilityReader;
use Modules\Shared\Domain\Money\Money;
use Modules\Shared\Support\MoneyFormatter;
use Modules\Storefront\Contracts\ProductCards;
use Plugin\Bundles\Persistence\Bundle;

/**
 * Dữ liệu hiển thị combo ở storefront: thẻ sản phẩm của từng món (Core ProductCards), biến thể để khách chọn size/màu,
 * giá trọn bộ tính từ giá thấp nhất của mỗi món và số tiền tiết kiệm. Combo có món đang ẩn/ngừng bán thì không hiện.
 */
final class BundlePresenter
{
    public const PER_PRODUCT = 3;

    public function __construct(
        private readonly ProductCards $cards,
        private readonly VariantDirectory $variants,
        private readonly AvailabilityReader $availability,
        private readonly MediaDirectory $media,
        private readonly MoneyFormatter $money,
    ) {}

    /**
     * Combo đang chạy có chứa sản phẩm.
     *
     * @return list<array<string, mixed>>
     */
    public function forStyle(int $styleId, string $locale): array
    {
        $bundles = Bundle::query()->with('items')->where('active', true)
            ->whereHas('items', fn ($query) => $query->where('style_id', $styleId))
            ->orderBy('position')->orderBy('id')->limit(self::PER_PRODUCT * 2)->get();

        return array_slice($this->present($bundles->all(), $locale), 0, self::PER_PRODUCT);
    }

    /** @return array<string, mixed>|null */
    public function find(int $bundleId, string $locale): ?array
    {
        $bundle = Bundle::query()->with('items')->find($bundleId);

        return $bundle === null ? null : ($this->present([$bundle], $locale)[0] ?? null);
    }

    /**
     * @param  list<Bundle>  $bundles
     * @return list<array<string, mixed>>
     */
    public function present(array $bundles, string $locale): array
    {
        $now = now()->getTimestamp();
        $bundles = array_values(array_filter($bundles, fn (Bundle $bundle): bool => $bundle->isRunningAt($now)));
        $styleIds = array_values(array_unique(array_merge(...array_map(fn (Bundle $bundle): array => array_keys($bundle->requirement()), $bundles ?: [new Bundle]))));
        if ($styleIds === []) {
            return [];
        }
        $cards = array_column($this->cards->forIds($styleIds, $locale), null, 'id');
        $variants = $this->variants->ofStyles($styleIds);
        $variantIds = array_merge(...array_map(fn (array $list): array => array_map(fn ($variant): int => $variant->id, $list), array_values($variants) ?: [[]]));
        $stock = $variantIds === [] ? [] : $this->availability->forVariants($variantIds);
        $images = $this->media->find(array_values(array_filter(array_map(fn (Bundle $bundle): ?int => $bundle->image_media_id, $bundles))));

        $result = [];
        foreach ($bundles as $bundle) {
            $presented = $this->bundle($bundle, $locale, $cards, $variants, $stock, $images);
            if ($presented !== null) {
                $result[] = $presented;
            }
        }

        return $result;
    }

    /**
     * @param  array<int, array<string, mixed>>  $cards
     * @param  array<int, list<\Modules\Catalog\Contracts\Data\VariantData>>  $variants
     * @param  array<int, int>  $stock
     * @param  array<int, mixed>  $images
     * @return array<string, mixed>|null
     */
    private function bundle(Bundle $bundle, string $locale, array $cards, array $variants, array $stock, array $images): ?array
    {
        $items = [];
        $total = 0;
        $currency = null;
        $priceHidden = false;
        foreach ($bundle->items as $item) {
            $card = $cards[$item->style_id] ?? null;
            $options = array_values(array_filter(array_map(fn ($variant): ?array => $variant->status !== 'active' ? null : [
                'id' => $variant->id,
                'label' => implode(' / ', array_column($variant->options, 'value')) ?: $variant->sku,
                'available' => ($stock[$variant->id] ?? 0) >= $item->quantity,
            ], $variants[$item->style_id] ?? [])));
            if ($card === null || $options === []) {
                return null;
            }
            $priceHidden = $priceHidden || ! empty($card['price_hidden']);
            $min = $card['price']['min'] ?? null;
            if ($min !== null) {
                $total += $min['amount'] * $item->quantity;
                $currency = $min['currency'];
            }
            $items[] = ['product' => $card, 'quantity' => $item->quantity, 'variants' => $options];
        }

        $saving = intdiv($total * $bundle->discount_basis_points, 10_000);

        return [
            'id' => $bundle->id,
            'slug' => $bundle->slug,
            'name' => $bundle->text('name', $locale),
            'description' => $bundle->text('description', $locale),
            'image_url' => ($images[$bundle->image_media_id ?? 0] ?? null)?->urlFor(800),
            'discount_percent' => intdiv($bundle->discount_basis_points, 100),
            'items' => $items,
            'price_hidden' => $priceHidden,
            'total' => $priceHidden || $currency === null ? null : $this->money->toArray(Money::of($total - $saving, $currency)),
            'compare_at' => $priceHidden || $currency === null ? null : $this->money->toArray(Money::of($total, $currency)),
            'saving' => $priceHidden || $currency === null ? null : $this->money->toArray(Money::of($saving, $currency)),
        ];
    }
}
