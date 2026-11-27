<?php

declare(strict_types=1);

namespace Modules\Customer\Contracts\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Mã OTP đăng ký (0.5.114) — chung cho `POST /auth/register/verify-otp` và trang nhập mã của Blade (ADR-044 §2). Blade lấy
 * SĐT từ bước đăng ký trong phiên nên `phone` không bắt buộc ở đây; API thiếu SĐT → `customer.phone_invalid`.
 */
final class RegisterOtpRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:20'],
            'code' => ['required', 'string', 'max:10'],
            'device' => ['nullable', 'string', 'max:64'],
            'cart_id' => ['nullable', 'string', 'size:26'],
        ];
    }
}
