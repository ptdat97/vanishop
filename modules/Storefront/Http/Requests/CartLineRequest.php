<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Catalog\Contracts\Data\VariantOptionsText;
use Modules\Catalog\Contracts\VariantDirectory;

/**
 * Thêm vào giỏ — một hợp đồng đầu vào cho Storefront API (`POST /carts/{id}/lines`) và Blade (`POST /gio-hang`)
 * (ADR-044 §2). Không gửi số lượng = 1. `options` là tuỳ chọn dòng của plugin (CartLineOption), chuẩn hoá ở Cart.
 */
final class CartLineRequest extends FormRequest
{
    public const MAX_ITEMS = 10;

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $api = $this->is('api/*');

        return [
            'variant_id' => ['nullable', $api ? 'required' : 'required_without:items', 'integer', 'min:1'],
            // Thêm nhiều món một lần (vd. cả bộ combo của plugin): thêm tất cả hoặc không món nào (Carts::addLines).
            'items' => [$api ? 'prohibited' : 'nullable', 'array', 'min:1', 'max:'.self::MAX_ITEMS],
            'items.*.variant_id' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'buy_now' => ['nullable', 'boolean'],
            // Bộ chọn của trang sản phẩm (ADR-045) gửi kèm giá trị trục đầu đang chọn `axis[<mã loại>]=<mã giá trị>` (khác `options` — tuỳ chọn dòng của plugin): biến thể
            // phải mang đúng giá trị đó (đổi giá trị trục đầu sau khi đã chọn SKU thì SKU của nhóm cũ có thể vẫn được chọn ngầm).
            'axis' => ['nullable', 'array', 'max:3'],
            'axis.*' => ['string', 'max:32'],
        ];
    }

    /**
     * @return list<\Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $selected = array_filter((array) $this->input('axis', []), fn ($code): bool => is_string($code) && $code !== '');
            if ($selected === [] || $validator->errors()->isNotEmpty()) {
                return;
            }
            $variant = app(VariantDirectory::class)->find([(int) $this->input('variant_id')])[(int) $this->input('variant_id')] ?? null;
            foreach ($selected as $type => $code) {
                if ($variant !== null && VariantOptionsText::code($variant->options, (string) $type) !== $code) {
                    $validator->errors()->add('variant_id', __('storefront::messages.choose_variant'));

                    return;
                }
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        // Đã chọn giá trị trục đầu ở bộ chọn của trang sản phẩm → chỉ còn thiếu các trục còn lại.
        $chooseVariant = __(array_filter((array) $this->input('axis', [])) !== [] ? 'storefront::messages.choose_remaining' : 'storefront::messages.choose_variant');

        return ['variant_id.required' => $chooseVariant, 'variant_id.required_without' => $chooseVariant, 'items.*.variant_id.required' => __('storefront::messages.choose_variant')];
    }

    /**
     * @return list<array{variant_id: int, quantity: int}>|null null = thêm một món qua `variant_id`
     */
    public function items(): ?array
    {
        $items = $this->validated('items');

        return $items === null ? null : array_map(fn (array $item): array => [
            'variant_id' => (int) $item['variant_id'], 'quantity' => (int) ($item['quantity'] ?? 1),
        ], array_values($items));
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
