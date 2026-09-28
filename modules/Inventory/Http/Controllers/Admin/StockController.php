<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Brand\Http\Controllers\BrandWorkspaceHome;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Identity\Contracts\Data\ScopeRef;
use Modules\Inventory\Application\StockAdjustmentService;
use Modules\Inventory\Application\StockQueries;
use Modules\Inventory\Http\Requests\StockChangeRequest;
use Modules\Inventory\Persistence\Models\Location;

final class StockController
{
    public function home(BrandWorkspaceHome $home): Response|RedirectResponse
    {
        Gate::authorize('inventory.view');

        return $home->respond('admin.inventory.stock.index', 'Tồn kho', 'Chọn brand để xem và điều chỉnh tồn kho.');
    }

    /**
     * Lưới tồn: variant của một mã sản phẩm × location bán brand.
     */
    public function index(Brand $brand, Request $request, VariantDirectory $variants, StockQueries $queries): Response
    {
        Gate::authorize('inventory.view', [ScopeRef::brand($brand->id)]);

        $styleCode = trim((string) $request->query('style', ''));
        $rows = $styleCode === '' ? [] : $variants->ofStyleCode($brand->id, $styleCode);
        $locations = $queries->locationsForBrand($brand->id);
        $levels = $queries->levels(array_map(fn ($variant): int => $variant->id, $rows));

        return Inertia::render('Inventory::Stock/Index', [
            'brand' => ['name' => $brand->name, 'slug' => $brand->slug],
            'baseUrl' => route('admin.inventory.stock.index'),
            'locationsUrl' => Gate::allows('inventory.locations.manage', [ScopeRef::owner()]) ? route('admin.inventory.locations.index') : null,
            'styleCode' => $styleCode,
            'styleName' => $rows[0]->styleName ?? null,
            'locations' => array_map(fn (Location $location): array => [
                'id' => $location->id,
                'code' => $location->code,
                'name' => $location->name,
                'external' => ! $location->managesOnHand(),
            ], $locations),
            'variants' => array_map(fn ($variant): array => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'color_code' => $variant->colorCode,
                'size_code' => $variant->sizeCode,
                'status' => $variant->status,
                'levels' => array_map(fn (Location $location): array => $levels[$location->id.':'.$variant->id] ?? ['on_hand' => 0, 'reserved' => 0, 'safety_stock' => 0, 'available' => 0], $locations),
            ], $rows),
            'canAdjust' => Gate::allows('inventory.adjust', [ScopeRef::brand($brand->id)]),
        ]);
    }

    public function change(Brand $brand, StockChangeRequest $request, VariantDirectory $variants, StockAdjustmentService $stock, StockQueries $queries): RedirectResponse
    {
        $variantId = (int) $request->validated('variant_id');
        $variant = $variants->find([$variantId])[$variantId] ?? null;
        $location = collect($queries->locationsForBrand($brand->id))->firstWhere('id', (int) $request->validated('location_id'));
        abort_if($variant === null || $variant->brandId !== $brand->id || $location === null, 404);

        $quantity = (int) $request->validated('quantity');
        $reason = (string) $request->validated('reason');

        match ((string) $request->validated('action')) {
            'adjust' => $stock->adjust($location, $variantId, $quantity, $reason),
            'count' => $stock->count($location, $variantId, $quantity, $reason),
            'safety' => $stock->setSafetyStock($location, $variantId, max(0, $quantity)),
        };

        return back()->with('success', __('inventory::messages.adjusted'));
    }

    public function movements(Brand $brand, Request $request, VariantDirectory $variants, StockQueries $queries): Response
    {
        Gate::authorize('inventory.view', [ScopeRef::brand($brand->id)]);

        $variantId = (int) $request->query('variant');
        $variant = $variants->find([$variantId])[$variantId] ?? null;
        abort_if($variant === null || $variant->brandId !== $brand->id, 404);

        return Inertia::render('Inventory::Stock/Movements', [
            'brand' => ['name' => $brand->name, 'slug' => $brand->slug],
            'backUrl' => route('admin.inventory.stock.index', ['style' => $variant->styleCode]),
            'variant' => ['sku' => $variant->sku, 'style_code' => $variant->styleCode],
            'movements' => $queries->movements($variantId),
        ]);
    }
}
