<?php

declare(strict_types=1);

namespace Modules\Customer\Contracts\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Shared\Support\Phones;

/**
 * Đăng ký bằng email — một hợp đồng đầu vào cho Storefront API (`POST /auth/register`) và form của Blade (ADR-044 §2).
 */
final class RegisterRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:20', fn (string $attribute, mixed $value, Closure $fail) => Phones::parse((string) $value) === null ? $fail(__('customer::messages.phone_invalid')) : null],
            'password' => ['required', 'string', 'min:8', 'max:200', 'confirmed'],
            'marketing_email' => ['sometimes', 'boolean'],
            // Storefront API: tên thiết bị của phiên và giỏ vãng lai để gộp khi tài khoản được tạo ngay (0.5.114).
            'device' => ['nullable', 'string', 'max:64'],
            'cart_id' => ['nullable', 'string', 'size:26'],
        ];
    }
}
