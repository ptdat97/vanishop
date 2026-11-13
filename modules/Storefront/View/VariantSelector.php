<?php

declare(strict_types=1);

namespace Modules\Storefront\View;

/**
 * Dữ liệu cho bộ chọn biến thể của trang sản phẩm (ADR-045, 1–3 trục) dựng từ chi tiết sản phẩm của Storefront API
 * (`options`, `variants`, `media.gallery`) — theme chỉ hiển thị, không tự tính tổ hợp.
 *
 * Không cần JS: trục đầu là nhóm radio `axis[<mã loại>]` (ô màu, nút hoặc danh sách thả xuống); mỗi giá trị của trục đầu có
 * một nhóm SKU (`variant_id`) nhãn là giá trị các trục còn lại ("M", "M / Cotton"), chỉ nhóm của giá trị đang chọn hiện ra (CSS
 * `:has()`). Một trục: chính các SKU là lựa chọn, SKU mặc định được chọn sẵn. `?sku=` chọn sẵn đúng SKU (nếu đang bán).
 */
final class VariantSelector
{
    /**
     * @param  array<string, mixed>  $product  chi tiết sản phẩm (Storefront API)
     * @return array{
     *     primary: array{code: string, name: string, display_type: string, single: bool, values: list<array<string, mixed>>}|null,
     *     groups: array<string, list<array<string, mixed>>>,
     *     rest_names: list<string>,
     *     hidden_variant_id: int|null
     * }
     */
    public static function for(array $product, ?string $requestedSku = null): array
    {
        $options = array_values((array) ($product['options'] ?? []));
        $variants = array_values(array_filter((array) ($product['variants'] ?? []), fn (array $variant): bool => (bool) $variant['is_active']));
        $gallery = collect((array) ($product['media']['gallery'] ?? []))->keyBy('id');
        $requested = $requestedSku === null || $requestedSku === '' ? null
            : collect($variants)->first(fn (array $variant): bool => strcasecmp((string) $variant['sku'], $requestedSku) === 0);
        $default = $requested ?? collect($variants)->firstWhere('is_default', true) ?? ($variants[0] ?? null);

        // Không có trục (một SKU): gửi kèm id SKU ẩn.
        if ($options === []) {
            return ['primary' => null, 'groups' => [], 'rest_names' => [], 'hidden_variant_id' => $default === null ? null : (int) $default['id']];
        }

        $valueOf = fn (array $variant, string $option): ?int => collect($variant['option_values'])->firstWhere('option', $option)['value_id'] ?? null;
        $valuesById = [];
        foreach ($options as $option) {
            foreach ($option['values'] as $value) {
                $valuesById[$option['code']][$value['id']] = $value;
            }
        }
        $images = fn (array $variant): array => collect((array) ($variant['media']['image_ids'] ?? []))->map(fn ($id) => $gallery->get($id))->filter()->values()->all();
        $hidePrice = ! empty($product['price_hidden']);
        $sku = fn (array $variant, string $label): array => [
            'id' => (int) $variant['id'], 'sku' => (string) $variant['sku'], 'label' => $label !== '' ? $label : (string) $variant['sku'],
            // Giá bị ẩn (khách chưa đăng nhập): vẫn chọn được; thêm giỏ chuyển sang đăng nhập.
            'disabled' => $variant['availability'] === 'out_of_stock' || ($variant['price'] === null && ! $hidePrice),
            'availability' => $variant['availability'], 'price' => $variant['price'], 'images' => $images($variant),
        ];

        $primary = $options[0];
        $rest = array_slice($options, 1);
        $single = $rest === [];
        $groups = [];
        $values = [];
        foreach ($primary['values'] as $value) {
            $members = array_values(array_filter($variants, fn (array $variant): bool => $valueOf($variant, $primary['code']) === $value['id']));
            if ($members === []) {
                continue;
            }
            $item = [
                'id' => (int) $value['id'], 'code' => (string) $value['code'], 'name' => (string) ($value['name'] ?? $value['code']),
                'hex' => $value['hex'] ?? null, 'image_url' => $value['image_url'] ?? null, 'available' => (bool) ($value['available'] ?? true),
                'checked' => $default !== null && $valueOf($default, $primary['code']) === $value['id'], 'images' => $images($members[0]),
            ];
            if ($single) {
                // Một trục: lựa chọn là chính SKU.
                $item['variant'] = $sku($members[0], '');
            } else {
                $groups[$item['code']] = array_map(function (array $variant) use ($sku, $rest, $valueOf, $valuesById, $requested): array {
                    $label = implode(' / ', array_filter(array_map(function (array $option) use ($variant, $valueOf, $valuesById): string {
                        $value = $valuesById[$option['code']][$valueOf($variant, $option['code'])] ?? null;

                        return (string) ($value['name'] ?? $value['code'] ?? '');
                    }, $rest)));

                    return [...$sku($variant, $label), 'checked' => $requested !== null && (int) $requested['id'] === (int) $variant['id']];
                }, $members);
            }
            $values[] = $item;
        }
        if ($values !== [] && ! in_array(true, array_column($values, 'checked'), true)) {
            $values[0]['checked'] = true;
        }

        return [
            'primary' => ['code' => (string) $primary['code'], 'name' => (string) ($primary['name'] ?? $primary['code']), 'display_type' => (string) ($primary['display_type'] ?? 'button'), 'single' => $single, 'values' => $values],
            'groups' => $groups,
            'rest_names' => array_map(fn (array $option): string => (string) ($option['name'] ?? $option['code']), $rest),
            'hidden_variant_id' => null,
        ];
    }
}
