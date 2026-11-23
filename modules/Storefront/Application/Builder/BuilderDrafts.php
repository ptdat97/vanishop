<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Builder;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Contracts\CatalogReader;
use Modules\Catalog\Contracts\Data\ProductFilters;
use Modules\Storefront\Application\Blocks\BlockLayout;
use Modules\Storefront\Application\PageBlocks;
use Modules\Storefront\Contracts\Data\BlockContext;

final class BuilderDrafts
{
    public function __construct(private readonly PageBlocks $blocks, private readonly CatalogReader $catalog) {}

    public function preview(Request $request, BlockLayout $layout): JsonResponse
    {
        $input = $request->validate($this->blocks->inputRules($layout) + [
            'product_slug' => ['nullable', 'string', 'max:160'],
            'listing_type' => ['nullable', 'in:category,collection,brand,search'],
            'listing_slug' => ['nullable', 'string', 'max:160'],
        ]);
        $clean = $this->blocks->validate($input['blocks'], $layout)['blocks'];
        $slots = array_column($layout->zones, 'slot');
        $productId = null;
        $listingType = $input['listing_type'] ?? 'category';
        $listingSlug = $input['listing_slug'] ?? null;
        foreach (array_filter($slots) as $slot) {
            if (str_starts_with($slot, 'vani.storefront.pdp.') && $productId === null) {
                $productId = $this->sampleProduct($input['product_slug'] ?? null);
            }
            if (str_starts_with($slot, 'vani.storefront.plp.') && $listingSlug === null && $listingType === 'category') {
                $categories = $this->catalog->categoryTree(App::getLocale());
                $listingSlug = $categories[0]['children'][0]['slug'] ?? $categories[0]['slug'] ?? null;
            }
        }
        $zones = [];
        foreach ($layout->group($clean) as $key => $zoneBlocks) {
            $zone = $layout->zones[$key];
            $context = match (true) {
                $zone->slot === null => null,
                str_starts_with($zone->slot, 'vani.storefront.pdp.') => new BlockContext($zone->slot, productId: $productId),
                str_starts_with($zone->slot, 'vani.storefront.plp.') => new BlockContext($zone->slot, listingType: $listingType, listingSlug: $listingSlug),
                default => new BlockContext($zone->slot),
            };
            $zones[] = ['label' => $zone->label, 'blocks' => $this->blocks->render($zoneBlocks, blockContext: $context)];
        }

        return response()->json(['html' => view('storefront::builder-preview', ['zones' => $zones])->render()])
            ->header('Cache-Control', 'private, no-store');
    }

    public function import(Request $request, BlockLayout $layout): JsonResponse
    {
        $input = $request->validate([
            'version' => ['required', 'integer', 'in:1'],
            'blocks' => ['present', 'array', 'list', 'max:'.PageBlocks::MAX_BLOCKS],
            'blocks.*' => ['array'],
            'blocks.*.type' => ['required', 'string', 'max:64'],
            'blocks.*.config' => ['nullable', 'array'],
            'blocks.*.zone' => ['nullable', 'string', 'max:160'],
        ]);
        $types = $this->blocks->types();
        $mapped = [];
        $counts = [];
        foreach ($input['blocks'] as $index => $block) {
            $type = $types[$block['type']] ?? null;
            if ($type === null) {
                throw ValidationException::withMessages(["blocks.{$index}.type" => __('storefront::texts.page_blocks.block_type_does_not_exist')]);
            }
            $allowed = $this->blocks->allowedZones($layout, $type);
            $preferred = $block['zone'] ?? null;
            if (in_array($preferred, $allowed, true)) {
                $allowed = [$preferred, ...array_diff($allowed, [$preferred])];
            }
            foreach ($allowed as $zone) {
                $limit = $layout->limit($zone);
                if ($limit !== null && ($counts[$zone] ?? 0) >= $limit) {
                    continue;
                }
                $mapped[$index] = [...$block, 'zone' => $zone];
                $counts[$zone] = ($counts[$zone] ?? 0) + 1;
                break;
            }
            if (! isset($mapped[$index])) {
                throw ValidationException::withMessages(["blocks.{$index}.zone" => __('storefront::messages.block_zone_rule')]);
            }
        }

        return response()->json(['blocks' => $this->blocks->validate($mapped, $layout)['blocks']]);
    }

    private function sampleProduct(?string $slug): ?int
    {
        $locale = App::getLocale();
        $now = now()->timestamp;
        if ($slug !== null) {
            $product = $this->catalog->productDetail($slug, $locale, $now);
            if ($product === null) {
                throw ValidationException::withMessages(['product_slug' => __('storefront::admin.builder.preview_product_missing')]);
            }

            return (int) $product['id'];
        }
        $products = $this->catalog->searchProducts(new ProductFilters(now: $now, perPage: 1), $locale)['items'];

        return isset($products[0]['id']) ? (int) $products[0]['id'] : null;
    }
}
