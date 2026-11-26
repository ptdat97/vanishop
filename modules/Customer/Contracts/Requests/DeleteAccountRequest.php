<?php

declare(strict_types=1);

namespace Modules\Customer\Contracts\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Khách tự xoá tài khoản (nhập lại mật khẩu hoặc mã một lần gửi qua email/SMS) — Storefront API `DELETE /me` và form Blade (ADR-044 §2).
 */
final class DeleteAccountRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            // Mật khẩu hiện tại hoặc mã một lần (`requestDeletionCode`, L40).
            'password' => ['required_without:code', 'nullable', 'string', 'max:200'],
            'code' => ['required_without:password', 'nullable', 'string', 'max:10'],
        ];
    }
}
