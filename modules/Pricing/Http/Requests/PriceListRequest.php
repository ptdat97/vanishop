<?php

declare(strict_types=1);

namespace Modules\Pricing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Identity\Contracts\Data\ScopeRef;
use Modules\Pricing\Domain\PriceListType;
use Modules\Pricing\Persistence\Models\PriceList;

final class PriceListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('pricing.manage', [ScopeRef::brand($this->brand()->id)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $list = $this->route('priceList');

        return [
            'code' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9][a-z0-9_-]*$/',
                Rule::unique('price_lists', 'code')->where('brand_id', $this->brand()->id)->ignore($list instanceof PriceList ? $list->id : null)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(PriceListType::class)],
            'priority' => ['required', 'integer', 'min:-1000', 'max:1000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'channel_ids' => ['array'],
            'channel_ids.*' => ['integer', 'distinct'],
            'lock_version' => [$list instanceof PriceList ? 'required' : 'nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array{code: string, name: string, type: string, priority: int, starts_at: ?string, ends_at: ?string, status: string}
     */
    public function toData(): array
    {
        return [
            'code' => (string) $this->validated('code'),
            'name' => (string) $this->validated('name'),
            'type' => (string) $this->validated('type'),
            'priority' => (int) $this->validated('priority'),
            'starts_at' => $this->validated('starts_at'),
            'ends_at' => $this->validated('ends_at'),
            'status' => (string) $this->validated('status'),
        ];
    }

    private function brand(): Brand
    {
        /** @var Brand $brand */
        $brand = $this->attributes->get('workspace_brand');

        return $brand;
    }
}
