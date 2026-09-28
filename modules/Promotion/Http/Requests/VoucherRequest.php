<?php

declare(strict_types=1);

namespace Modules\Promotion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Identity\Contracts\Data\ScopeRef;

final class VoucherRequest extends FormRequest
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
            'code' => ['nullable', 'required_without:count', 'string', 'max:32', 'regex:/^[A-Za-z0-9-]+$/'],
            'prefix' => ['nullable', 'string', 'max:16', 'regex:/^[A-Za-z0-9-]*$/'],
            'count' => ['nullable', 'required_without:code', 'integer', 'min:1', 'max:5000'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
        ];
    }
}
