<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Identity\Contracts\Data\ScopeRef;
use Modules\Inventory\Application\LocationService;
use Modules\Inventory\Domain\LocationType;
use Modules\Inventory\Http\Requests\LocationRequest;
use Modules\Inventory\Persistence\Models\Location;

/**
 * Kho/cửa hàng vật lý của cửa hàng.
 */
final class LocationController
{
    public function index(): Response
    {
        Gate::authorize('inventory.locations.manage', [ScopeRef::owner()]);

        return Inertia::render('Inventory::Locations/Index', [
            'baseUrl' => route('admin.inventory.locations.index'),
            'locations' => Location::query()->orderByDesc('priority')->orderBy('code')->get()->map(fn (Location $location): array => [
                'id' => $location->id,
                'code' => $location->code,
                'name' => $location->name,
                'type' => $location->type->value,
                'priority' => $location->priority,
                'status' => $location->status,
                'ships_online_orders' => $location->ships_online_orders,
                'stock_authority' => $location->stock_authority,
            ])->all(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('inventory.locations.manage', [ScopeRef::owner()]);

        return $this->form(null);
    }

    public function store(LocationRequest $request, LocationService $locations): RedirectResponse
    {
        $location = $locations->save($request->toData());

        return redirect()->route('admin.inventory.locations.edit', ['location' => $location->id])->with('success', __('inventory::messages.saved'));
    }

    public function edit(Location $location): Response
    {
        Gate::authorize('inventory.locations.manage', [ScopeRef::owner()]);

        return $this->form($location);
    }

    public function update(Location $location, LocationRequest $request, LocationService $locations): RedirectResponse
    {
        $locations->save($request->toData(), $location, (int) $request->validated('lock_version'));

        return back()->with('success', __('inventory::messages.saved'));
    }

    private function form(?Location $location): Response
    {
        return Inertia::render('Inventory::Locations/Form', [
            'baseUrl' => route('admin.inventory.locations.index'),
            'location' => $location === null ? null : [
                ...$location->only(['id', 'code', 'name', 'address', 'province_code', 'ships_online_orders', 'allows_pickup', 'accepts_returns', 'stock_authority', 'priority', 'status', 'lock_version']),
                'type' => $location->type->value,
            ],
            'types' => array_column(LocationType::cases(), 'value'),
        ]);
    }
}
