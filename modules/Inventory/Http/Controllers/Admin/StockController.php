<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Identity\Contracts\Data\ScopeRef;
use Modules\Inventory\Application\StockAdjustmentService;
use Modules\Inventory\Application\StockQueries;
use Modules\Inventory\Http\Requests\StockChangeRequest;
use Modules\Inventory\Persistence\Models\Location;

final class StockController
{
    public function home(): RedirectResponse
    {
        Gate::authorize('inventory.view');

        return redirect()->route('admin.inventory.stock.index');
    }

    /**
     * Lưới tồn: variant của một mã sản phẩm × location.
     */
    public function index(Request $request, VariantDirectory $variants, StockQueries $queries): Response
    {
        Gate::authorize('inventory.view');

        $styleCode = trim((string) $request->query('style', ''));
        $rows = $styleCode === '' ? [] : $variants->ofStyleCode($styleCode);
        $locations = $queries->locations();
        $levels = $queries->levels(array_map(fn ($variant): int => $variant->id, $rows));

        return Inertia::render('Inventory::Stock/Index', [
            'baseUrl' => route('admin.inventory.stock.index'),
            'locationsUrl' => Gate::allows('inventory.locations.manage', [ScopeRef::owner()]) ? route('admin.inventory.locations.index') : null,
            'transfersUrl' => route('admin.inventory.transfers.index'),
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
            'canAdjust' => Gate::allows('inventory.adjust'),
        ]);
    }

    public function change(StockChangeRequest $request, VariantDirectory $variants, StockAdjustmentService $stock, StockQueries $queries): RedirectResponse
    {
        $variantId = (int) $request->validated('variant_id');
        $variant = $variants->find([$variantId])[$variantId] ?? null;
        $location = collect($queries->locations())->firstWhere('id', (int) $request->validated('location_id'));
        abort_if($variant === null || $location === null, 404);

        $quantity = (int) $request->validated('quantity');
        $reason = (string) $request->validated('reason');

        match ((string) $request->validated('action')) {
            'adjust' => $stock->adjust($location, $variantId, $quantity, $reason),
            'count' => $stock->count($location, $variantId, $quantity, $reason),
            'safety' => $stock->setSafetyStock($location, $variantId, max(0, $quantity)),
        };

        return back()->with('success', __('inventory::messages.adjusted'));
    }

    public function movements(Request $request, VariantDirectory $variants, StockQueries $queries): Response
    {
        Gate::authorize('inventory.view');

        $variantId = (int) $request->query('variant');
        $variant = $variants->find([$variantId])[$variantId] ?? null;
        abort_if($variant === null, 404);

        return Inertia::render('Inventory::Stock/Movements', [
            'backUrl' => route('admin.inventory.stock.index', ['style' => $variant->styleCode]),
            'variant' => ['sku' => $variant->sku, 'style_code' => $variant->styleCode],
            'movements' => $queries->movements($variantId),
        ]);
    }
}
