<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Identity\Contracts\Data\ScopeRef;

/**
 * Một thay đổi tồn tại (location, variant): adjust (±delta), count (đặt số kiểm kê), safety (tồn an toàn).
 */
final class StockChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Brand $brand */
        $brand = $this->attributes->get('workspace_brand');

        return Gate::allows('inventory.adjust', [ScopeRef::brand($brand->id)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'location_id' => ['required', 'integer'],
            'variant_id' => ['required', 'integer'],
            'action' => ['required', Rule::in(['adjust', 'count', 'safety'])],
            'quantity' => ['required', 'integer', 'min:-100000', 'max:1000000'],
            'reason' => ['required_unless:action,safety', 'nullable', 'string', 'max:255'],
        ];
    }
}
