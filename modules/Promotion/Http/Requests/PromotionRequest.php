<?php

declare(strict_types=1);

namespace Modules\Promotion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Identity\Contracts\Data\ScopeRef;
use Modules\Promotion\Domain\Stacking;
use Modules\Promotion\Persistence\Models\Promotion;

final class PromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Brand $brand */
        $brand = $this->attributes->get('workspace_brand');

        return Gate::allows('promotion.manage', [ScopeRef::brand($brand->id)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'priority' => ['required', 'integer', 'min:-1000', 'max:1000'],
            'stacking' => ['required', Rule::enum(Stacking::class)],
            'requires_voucher' => ['required', 'boolean'],
            'action_type' => ['required', 'string', 'max:64'],
            'action_config' => ['required', 'array'],
            'action_config.basis_points' => ['nullable', 'integer'],
            'action_config.amount' => ['nullable', 'integer'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'budget_amount' => ['nullable', 'integer', 'min:1'],
            'lock_version' => [$this->route('promotion') instanceof Promotion ? 'required' : 'nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toData(): array
    {
        $data = collect($this->validated())->except('lock_version')->all();
        $data['action_config'] = array_filter((array) $data['action_config'], fn (mixed $value): bool => $value !== null);

        return $data + ['starts_at' => null, 'ends_at' => null, 'usage_limit' => null, 'budget_amount' => null];
    }
}
