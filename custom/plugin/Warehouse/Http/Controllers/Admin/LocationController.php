<?php

declare(strict_types=1);

namespace Plugin\Warehouse\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Identity\Contracts\Data\ScopeRef;
use Modules\Inventory\Contracts\Data\LocationData;
use Modules\Inventory\Contracts\LocationRegistry;
use Plugin\Warehouse\Http\Requests\LocationRequest;

/**
 * Kho & cửa hàng (vani.warehouse, ADR-042): nhiều kho, loại, khả năng, ưu tiên giữ hàng, authority ngoài. Ghi qua
 * LocationRegistry của Core (audit, optimistic lock).
 */
final class LocationController
{
    public function __construct(private readonly LocationRegistry $locations) {}

    public function index(): Response
    {
        Gate::authorize('warehouse.locations.manage', [ScopeRef::owner()]);

        return Inertia::render('Warehouse::Locations/Index', [
            'labels' => __('vani-warehouse::labels'),
            'baseUrl' => route('admin.plugins.vani-warehouse.locations.index'),
            'locations' => array_map(fn (LocationData $location): array => [
                'id' => $location->id, 'code' => $location->code, 'name' => $location->name, 'type' => $location->type,
                'priority' => $location->priority, 'status' => $location->status, 'ships_online_orders' => $location->shipsOnlineOrders,
                'stock_authority' => $location->stockAuthority,
            ], $this->locations->all()),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('warehouse.locations.manage', [ScopeRef::owner()]);

        return $this->form(null);
    }

    public function store(LocationRequest $request): RedirectResponse
    {
        $location = $this->locations->save($request->toData());

        return redirect()->route('admin.plugins.vani-warehouse.locations.edit', ['location' => $location->id])->with('success', __('vani-warehouse::messages.saved'));
    }

    public function edit(int $location): Response
    {
        Gate::authorize('warehouse.locations.manage', [ScopeRef::owner()]);

        return $this->form($this->locations->find($location) ?? abort(404));
    }

    public function update(int $location, LocationRequest $request): RedirectResponse
    {
        abort_if($this->locations->find($location) === null, 404);
        $this->locations->save($request->toData(), $location, (int) $request->validated('lock_version'));

        return back()->with('success', __('vani-warehouse::messages.saved'));
    }

    private function form(?LocationData $location): Response
    {
        return Inertia::render('Warehouse::Locations/Form', [
            'labels' => __('vani-warehouse::labels'),
            'baseUrl' => route('admin.plugins.vani-warehouse.locations.index'),
            'location' => $location?->toArray(),
            'types' => LocationData::TYPES,
        ]);
    }
}
