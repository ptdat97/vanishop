<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Modules\Identity\Contracts\Data\ScopeRef;
use Modules\Inventory\Domain\LocationType;
use Modules\Inventory\Persistence\Models\Location;

final class LocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('inventory.locations.manage', [ScopeRef::owner()]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $location = $this->route('location');

        return [
            'code' => ['required', 'string', 'max:32', 'regex:/^[A-Z0-9][A-Z0-9-]*$/', Rule::unique('locations', 'code')->ignore($location instanceof Location ? $location->id : null)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(LocationType::class)],
            'address' => ['nullable', 'string', 'max:255'],
            'province_code' => ['nullable', 'string', 'max:16'],
            'ships_online_orders' => ['required', 'boolean'],
            'allows_pickup' => ['required', 'boolean'],
            'accepts_returns' => ['required', 'boolean'],
            'stock_authority' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9][a-z0-9_-]*$/'],
            'priority' => ['required', 'integer', 'min:-1000', 'max:1000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'lock_version' => [$location instanceof Location ? 'required' : 'nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toData(): array
    {
        return collect($this->validated())->except(['lock_version'])->all();
    }
}
