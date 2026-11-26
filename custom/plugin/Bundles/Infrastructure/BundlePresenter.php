<?php

declare(strict_types=1);

namespace Plugin\Bundles\Infrastructure;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Modules\Catalog\Contracts\Data\VariantData;
use Modules\Catalog\Contracts\MediaDirectory;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Inventory\Contracts\AvailabilityReader;
use Modules\Pricing\Contracts\Data\PricingContext;
use Modules\Pricing\Contracts\Data\ResolvedPrice;
use Modules\Pricing\Contracts\PriceResolver;
use Modules\Shared\Domain\Money\Money;
use Modules\Shared\Support\MoneyFormatter;
use Modules\Storefront\Contracts\ProductCards;
use Plugin\Bundles\Domain\BundleConfig;
use Plugin\Bundles\Domain\BundleMath;
use Plugin\Bundles\Persistence\Bundle;

/**
 * Dữ liệu hiển thị combo ở storefront: thẻ sản phẩm của từng món (Core ProductCards), biến thể để khách chọn size/màu,
 * giá trọn bộ tính từ giá thấp nhất của mỗi món và số tiền tiết kiệm. Combo có món đang ẩn/ngừng bán thì không hiện.
 */
final class BundlePresenter
{
    public const PER_PRODUCT = 3;

    private const PER_PAGE = 8;

    public function __construct(
        private readonly ProductCards $cards,
        private readonly VariantDirectory $variants,
        private readonly AvailabilityReader $availability,
        private readonly PriceResolver $prices,
        private readonly MediaDirectory $media,
        private readonly MoneyFormatter $money,
    ) {}

    /**
     * Combo đang chạy có chứa sản phẩm.
     *
     * @return list<array<string, mixed>>
     */
    public function forProduct(int $productId, string $locale): array
    {
        $result = [];
        $this->running()
            ->whereHas('items', fn ($query) => $query->where('product_id', $productId))
            ->chunk(self::PER_PRODUCT, function (Collection $bundles) use (&$result, $locale): bool {
                array_push($result, ...$this->present($bundles->all(), $locale));

                return count($result) < self::PER_PRODUCT;
            });

        return array_slice($result, 0, self::PER_PRODUCT);
    }

    /** @return array{bundles: list<array<string, mixed>>, paginator: Paginator<int, Bundle>} */
    public function listing(string $locale): array
    {
        $paginator = $this->running()->simplePaginate(self::PER_PAGE);

        return [
            'bundles' => $this->present($paginator->items(), $locale),
            'paginator' => $paginator,
        ];
    }

    /** @return Builder<Bundle> */
    private function running(): Builder
    {
        $now = now()->utc();

        return Bundle::query()->with('items')->where('active', true)
            ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', $now))
            ->orderBy('position')->orderBy('id');
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
    private function present(array $bundles, string $locale): array
    {
        $now = now()->getTimestamp();
        $bundles = array_values(array_filter($bundles, fn (Bundle $bundle): bool => $bundle->isRunningAt($now)));
        $productIds = array_values(array_unique(array_merge(...array_map(fn (Bundle $bundle): array => array_keys($bundle->requirement()), $bundles))));
        if ($productIds === []) {
            return [];
        }
        $cards = array_column($this->cards->forIds($productIds, $locale), null, 'id');
        $variants = $this->variants->ofProducts($productIds);
        $variantIds = array_merge(...array_map(fn (array $list): array => array_column($list, 'id'), array_values($variants)));
        $prices = $variantIds === [] ? [] : $this->prices->forVariants($variantIds, new PricingContext($now));
        $stock = $prices === [] ? [] : $this->availability->forVariants(array_keys($prices));
        $images = $this->media->find(array_values(array_unique(array_filter(array_map(fn (Bundle $bundle): ?int => $bundle->image_media_id, $bundles)))));

        $result = [];
        foreach ($bundles as $bundle) {
            $presented = $this->bundle($bundle, $locale, $cards, $variants, $prices, $stock, $images);
            if ($presented !== null) {
                $result[] = $presented;
            }
        }

        return $result;
    }

    /**
     * @param  array<int, array<string, mixed>>  $cards
     * @param  array<int, list<VariantData>>  $variants
     * @param  array<int, ResolvedPrice>  $prices
     * @param  array<int, int>  $stock
     * @param  array<int, mixed>  $images
     * @return array<string, mixed>|null
     */
    private function bundle(Bundle $bundle, string $locale, array $cards, array $variants, array $prices, array $stock, array $images): ?array
    {
        $requirement = BundleConfig::requirement(['items' => $bundle->requirement()]);
        if ($requirement === null) {
            return null;
        }
        $items = [];
        $priceLines = [];
        $currency = null;
        $priceHidden = false;
        foreach ($requirement as $productId => $quantity) {
            $card = $cards[$productId] ?? null;
            if ($card === null) {
                return null;
            }
            $priceHidden = $priceHidden || ! empty($card['price_hidden']);
            $options = [];
            $minimum = null;
            foreach ($variants[$productId] ?? [] as $variant) {
                $price = $prices[$variant->id]->amount ?? null;
                if ($variant->status !== 'active' || $price === null) {
                    continue;
                }
                $available = ($stock[$variant->id] ?? 0) >= $quantity;
                $options[] = [
                    'id' => $variant->id,
                    'label' => implode(' / ', array_column($variant->options, 'value')) ?: $variant->sku,
                    'available' => $available,
                ];
                if ($available && ($minimum === null || $price->amount < $minimum->amount)) {
                    $minimum = $price;
                }
            }
            if ($options === []) {
                return null;
            }
            $availableIds = array_column(array_filter($options, fn (array $option): bool => $option['available']), 'id');
            if ($minimum !== null) {
                $priceLines[$productId] = ['product_id' => $productId, 'quantity' => $quantity, 'unit_price' => $minimum->amount];
                $currency = $minimum->currency;
            }
            $items[] = [
                'product' => $card,
                'quantity' => $quantity,
                'variants' => $options,
                'available' => $minimum !== null,
                'selected_variant_id' => count($availableIds) === 1 ? $availableIds[0] : null,
            ];
        }

        $available = count($priceLines) === count($requirement);
        $showTotal = $available && ! $priceHidden && $currency !== null;
        $total = array_sum(array_map(fn (array $line): int => $line['unit_price'] * $line['quantity'], $priceLines));
        $saving = array_sum(BundleMath::discounts($priceLines, $requirement, $bundle->discount_basis_points));

        return [
            'id' => $bundle->id,
            'slug' => $bundle->slug,
            'name' => $bundle->text('name', $locale),
            'description' => $bundle->text('description', $locale),
            'image_url' => ($images[$bundle->image_media_id ?? 0] ?? null)?->urlFor(800),
            'discount_percent' => intdiv($bundle->discount_basis_points, 100),
            'items' => $items,
            'available' => $available,
            'total' => $showTotal ? $this->money->toArray(Money::of($total - $saving, $currency)) : null,
            'compare_at' => $showTotal ? $this->money->toArray(Money::of($total, $currency)) : null,
            'saving' => $showTotal ? $this->money->toArray(Money::of($saving, $currency)) : null,
        ];
    }
}
