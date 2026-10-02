<?php

declare(strict_types=1);

namespace Modules\Ordering\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class ShippingAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('orders.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'province_code' => ['required', 'string', 'max:16'],
            'province_name' => ['required', 'string', 'max:120'],
            'ward_code' => ['required', 'string', 'max:16'],
            'ward_name' => ['required', 'string', 'max:120'],
            'street_line' => ['required', 'string', 'max:255'],
            'reason' => ['required', 'string', 'max:255'],
            'lock_version' => ['required', 'integer', 'min:0'],
        ];
    }
}
