<?php

declare(strict_types=1);

namespace Modules\Customer\Contracts\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Đặt mật khẩu mới bằng link email — Storefront API (`POST /auth/password/reset`) và form của Blade (ADR-044 §2).
 */
final class ResetPasswordRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'size:64'],
            'password' => ['required', 'string', 'min:8', 'max:200', 'confirmed'],
        ];
    }
}
