<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Catalog\Contracts\Data\VariantData;
use Modules\Catalog\Contracts\ListingAvailability;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Catalog\Events\ProductListingProjected;
use Modules\Inventory\Contracts\AvailabilityReader;
use Modules\Inventory\Events\AvailabilityChanged;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

/**
 * Ghi "còn hàng" của sản phẩm sang read model trang danh sách của Catalog (db-refactor §4.3): tồn đổi (AvailabilityChanged)
 * hoặc Catalog vừa dựng lại sản phẩm (SKU mới / đổi trạng thái, `vani:catalog:rebuild-listing`). Còn hàng = có SKU đang bán
 * với ATS > 0 (sản phẩm không theo dõi tồn: ATS = UNLIMITED). Chạy nền, trễ vài giây.
 */
final class SyncListingAvailability implements ShouldQueue
{
    public string $queue = 'catalog-sync';

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    private const CHUNK = 500;

    public function __construct(
        private readonly VariantDirectory $variants,
        private readonly AvailabilityReader $availability,
        private readonly ListingAvailability $listing,
        private readonly CurrentContext $context,
    ) {}

    public function handle(AvailabilityChanged|ProductListingProjected $event): void
    {
        $productIds = $event instanceof ProductListingProjected
            ? $event->productIds
            : array_values(array_unique($this->variants->productIdsOf($event->variantIds)));

        $this->context->runAs(ContextScope::system('listing availability'), function () use ($productIds): void {
            foreach (array_chunk($productIds, self::CHUNK) as $chunk) {
                $byProduct = $this->variants->ofProducts($chunk);
                $active = array_merge([], ...array_map(fn (array $variants): array => array_values(array_filter($variants, fn (VariantData $variant): bool => $variant->status === 'active')), array_values($byProduct)));
                $ats = $this->availability->forVariants(array_map(fn (VariantData $variant): int => $variant->id, $active));
                $inStock = array_fill_keys($chunk, false);
                foreach ($active as $variant) {
                    if (($ats[$variant->id] ?? 0) > 0) {
                        $inStock[$variant->productId] = true;
                    }
                }
                $this->listing->setInStock($inStock);
            }
        });
    }
}
