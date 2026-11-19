<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Thanh toán lại đơn chưa thanh toán — Storefront API và Blade dùng chung (ADR-044 §2). `gateway` tuỳ chọn: app đổi
 * cổng; form Blade không gửi (dùng cổng của đơn).
 */
final class RetryPaymentRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'gateway' => ['nullable', 'string', 'max:64'],
        ];
    }
}
