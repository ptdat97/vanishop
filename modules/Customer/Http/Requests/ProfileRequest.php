<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Hồ sơ khách — một hợp đồng đầu vào cho Storefront API (`PATCH /me`, sửa một phần) và form hồ sơ của Blade (gửi đủ)
 * (ADR-044 §2).
 */
final class ProfileRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $sometimes = $this->isMethod('PATCH') ? ['sometimes'] : [];

        return [
            'full_name' => [...$sometimes, 'required', 'string', 'max:120'],
            'email' => [...$sometimes, 'nullable', 'email', 'max:190'],
            'birth_date' => [...$sometimes, 'nullable', 'date', 'before:today', 'after:1900-01-01'],
            'gender' => [...$sometimes, 'nullable', Rule::in(['female', 'male', 'other'])],
        ];
    }
}
