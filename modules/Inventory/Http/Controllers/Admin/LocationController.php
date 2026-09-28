<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Brand\Contracts\BrandDirectory;
use Modules\Channel\Contracts\ChannelDirectory;
use Modules\Identity\Contracts\Data\ScopeRef;
use Modules\Inventory\Application\LocationService;
use Modules\Inventory\Domain\LocationType;
use Modules\Inventory\Http\Requests\LocationRequest;
use Modules\Inventory\Persistence\Models\Location;
use Modules\Tenancy\Contracts\LegalEntityDirectory;

/**
 * Kho/cửa hàng — cấp Owner (dùng chung cho nhiều brand).
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

    public function create(BrandDirectory $brands, ChannelDirectory $channels, LegalEntityDirectory $entities): Response
    {
        Gate::authorize('inventory.locations.manage', [ScopeRef::owner()]);

        return $this->form(null, $brands, $channels, $entities, null);
    }

    public function store(LocationRequest $request, LocationService $locations): RedirectResponse
    {
        $location = $locations->save($request->toData(), array_map('intval', (array) $request->validated('brand_ids', [])), array_map('intval', (array) $request->validated('channel_ids', [])));

        return redirect()->route('admin.inventory.locations.edit', ['location' => $location->id])->with('success', __('inventory::messages.saved'));
    }

    public function edit(Location $location, BrandDirectory $brands, ChannelDirectory $channels, LegalEntityDirectory $entities, LocationService $locations): Response
    {
        Gate::authorize('inventory.locations.manage', [ScopeRef::owner()]);

        return $this->form($location, $brands, $channels, $entities, $locations->assignments($location->id));
    }

    public function update(Location $location, LocationRequest $request, LocationService $locations): RedirectResponse
    {
        $locations->save($request->toData(), array_map('intval', (array) $request->validated('brand_ids', [])), array_map('intval', (array) $request->validated('channel_ids', [])), $location, (int) $request->validated('lock_version'));

        return back()->with('success', __('inventory::messages.saved'));
    }

    /**
     * @param  array{brand_ids: list<int>, channel_ids: list<int>}|null  $assignments
     */
    private function form(?Location $location, BrandDirectory $brands, ChannelDirectory $channels, LegalEntityDirectory $entities, ?array $assignments): Response
    {
        return Inertia::render('Inventory::Locations/Form', [
            'baseUrl' => route('admin.inventory.locations.index'),
            'location' => $location === null ? null : [
                ...$location->only(['id', 'code', 'name', 'legal_entity_id', 'address', 'province_code', 'ships_online_orders', 'allows_pickup', 'accepts_returns', 'stock_authority', 'priority', 'status', 'lock_version']),
                'type' => $location->type->value,
                ...$assignments,
            ],
            'brands' => array_map(fn ($brand): array => ['id' => $brand->id, 'name' => $brand->name], $brands->list(null)),
            'channels' => $channels->all(),
            'legalEntities' => $entities->all(),
            'types' => array_column(LocationType::cases(), 'value'),
        ]);
    }
}
