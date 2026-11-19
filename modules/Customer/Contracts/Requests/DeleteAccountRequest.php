<?php

declare(strict_types=1);

namespace Modules\Customer\Contracts\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Khách tự xoá tài khoản (nhập lại mật khẩu) — Storefront API `DELETE /me` và form Blade (ADR-044 §2).
 */
final class DeleteAccountRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'max:200'],
        ];
    }
}
