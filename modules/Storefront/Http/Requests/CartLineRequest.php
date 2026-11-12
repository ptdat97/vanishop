<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Catalog\Contracts\VariantDirectory;

/**
 * Thêm vào giỏ — một hợp đồng đầu vào cho Storefront API (`POST /carts/{id}/lines`) và Blade (`POST /gio-hang`)
 * (ADR-044 §2). Không gửi số lượng = 1. `options` là tuỳ chọn dòng của plugin (CartLineOption), chuẩn hoá ở Cart.
 */
final class CartLineRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'variant_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'buy_now' => ['nullable', 'boolean'],
            // Bộ chọn màu → size của trang sản phẩm gửi kèm màu đang chọn: biến thể phải thuộc màu đó (không JS, đổi màu sau
            // khi đã chọn size thì size của màu cũ vẫn được chọn ngầm trong nhóm đang ẩn).
            'color' => ['nullable', 'string', 'max:32'],
        ];
    }

    /**
     * @return list<\Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $color = $this->input('color');
            if (! is_string($color) || $color === '' || $validator->errors()->isNotEmpty()) {
                return;
            }
            $variant = app(VariantDirectory::class)->find([(int) $this->input('variant_id')])[(int) $this->input('variant_id')] ?? null;
            if ($variant !== null && $variant->colorCode !== $color) {
                $validator->errors()->add('variant_id', __('storefront::messages.choose_variant'));
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['variant_id.required' => __('storefront::messages.choose_variant')];
    }

    public function variantId(): int
    {
        return (int) $this->validated('variant_id');
    }

    public function quantity(): int
    {
        return (int) ($this->validated('quantity') ?? 1);
    }

    /**
     * @return array<string, array<string, scalar|null>>
     */
    public function lineOptions(): array
    {
        return LineOptions::from($this->input('options'));
    }
}
