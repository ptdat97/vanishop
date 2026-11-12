<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Customer\Http\Middleware\AuthenticateCustomer;

/**
 * Đặt/đổi mật khẩu khách — một hợp đồng đầu vào cho Storefront API (`PUT /me/password`) và form của Blade (ADR-044 §2).
 * Khách đã có mật khẩu thì phải nhập mật khẩu hiện tại (AuthService vẫn kiểm đúng/sai).
 */
final class PasswordRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        $hasPassword = AuthenticateCustomer::customer($this)->password !== null;

        return [
            'current_password' => [$hasPassword ? 'required' : 'nullable', 'string', 'max:200'],
            'password' => ['required', 'string', 'min:8', 'max:200', 'confirmed'],
        ];
    }
}
