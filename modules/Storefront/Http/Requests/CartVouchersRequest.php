<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Cart\Contracts\Carts;

/**
 * Đặt mã giảm giá của giỏ — Storefront API `PUT /carts/{id}/vouchers` (`voucher_codes[]`) và ô mã của Blade
 * `POST /gio-hang/ma-giam-gia` (`code`: một mã thay toàn bộ danh sách, rỗng = bỏ mã) (ADR-044 §2, §5).
 */
final class CartVouchersRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->has('voucher_codes')) {
            $code = trim((string) $this->input('code', ''));
            $this->merge(['voucher_codes' => $code === '' ? [] : [$code]]);
        }
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'voucher_codes' => ['present', 'array', 'max:'.Carts::MAX_VOUCHER_CODES],
            'voucher_codes.*' => ['string', 'max:64'],
        ];
    }

    /**
     * @return list<string>
     */
    public function codes(): array
    {
        return array_values(array_map('strval', (array) $this->validated('voucher_codes')));
    }
}
