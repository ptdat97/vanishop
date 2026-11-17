<?php

declare(strict_types=1);

namespace Modules\Customer\Contracts\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Đăng ký bằng email — một hợp đồng đầu vào cho Storefront API (`POST /auth/register`) và form của Blade (ADR-044 §2).
 */
final class RegisterRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:190'],
            'password' => ['required', 'string', 'min:8', 'max:200', 'confirmed'],
        ];
    }
}
