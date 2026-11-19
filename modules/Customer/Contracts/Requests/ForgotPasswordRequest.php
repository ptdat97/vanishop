<?php

declare(strict_types=1);

namespace Modules\Customer\Contracts\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Gửi link đặt lại mật khẩu — Storefront API `POST /auth/password/forgot` và form Blade (ADR-044 §2).
 */
final class ForgotPasswordRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:190'],
        ];
    }
}
