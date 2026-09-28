<?php

declare(strict_types=1);

namespace Modules\Pricing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Identity\Contracts\Data\ScopeRef;
use Modules\Pricing\Application\PriceListService;

final class PricesRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Brand $brand */
        $brand = $this->attributes->get('workspace_brand');

        return Gate::allows('pricing.manage', [ScopeRef::brand($brand->id)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'prices' => ['required', 'array', 'max:500'],
            'prices.*.variant_id' => ['required', 'integer', 'distinct'],
            'prices.*.amount' => ['nullable', 'integer', 'min:0', 'max:'.PriceListService::MAX_AMOUNT],
            'prices.*.compare_at_amount' => ['nullable', 'integer', 'min:0', 'max:'.PriceListService::MAX_AMOUNT],
        ];
    }

    /**
     * @return list<array{variant_id: int, amount: int|null, compare_at_amount: int|null}>
     */
    public function rows(): array
    {
        return array_map(fn (array $row): array => [
            'variant_id' => (int) $row['variant_id'],
            'amount' => isset($row['amount']) ? (int) $row['amount'] : null,
            'compare_at_amount' => isset($row['compare_at_amount']) ? (int) $row['compare_at_amount'] : null,
        ], array_values((array) $this->validated('prices')));
    }
}
