<?php

declare(strict_types=1);

namespace Modules\Customer\Contracts\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Đăng nhập bằng email + mật khẩu — Storefront API `POST /auth/login` và form Blade (ADR-044 §2). `device`, `cart_id` chỉ API gửi.
 */
final class LoginRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:190'],
            'password' => ['required', 'string', 'max:200'],
            'device' => ['nullable', 'string', 'max:64'],
            'cart_id' => ['nullable', 'string', 'size:26'],
        ];
    }
}
