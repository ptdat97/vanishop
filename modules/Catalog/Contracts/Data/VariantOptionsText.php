<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts\Data;

/**
 * Hiển thị tổ hợp tuỳ chọn của một SKU (ADR-045) — snapshot `list<{type, name, code, value}>` theo thứ tự trục, như trong
 * `VariantData::$options`, `SellableVariant::$options`, dòng giỏ/đơn (`variantOptions`). Dùng chung cho Admin, storefront,
 * phiếu in, file giao hãng, payload tích hợp.
 */
final class VariantOptionsText
{
    /**
     * Giá trị nối bằng " / ", vd. "Trắng ngà / M"; rỗng khi sản phẩm không có trục.
     *
     * @param  list<array{type?: string, name?: string, code?: string, value?: string}>  $options
     */
    public static function label(array $options): string
    {
        return implode(' / ', array_filter(array_map(fn (array $option): string => (string) ($option['value'] ?? $option['code'] ?? ''), $options), fn (string $value): bool => $value !== ''));
    }

    /**
     * Kèm tên loại, vd. "Màu: Trắng ngà, Size: M".
     *
     * @param  list<array{type?: string, name?: string, code?: string, value?: string}>  $options
     */
    public static function describe(array $options): string
    {
        return implode(', ', array_map(fn (array $option): string => trim(((string) ($option['name'] ?? $option['type'] ?? '')).': '.((string) ($option['value'] ?? $option['code'] ?? '')), ': '), $options));
    }

    /**
     * Mã giá trị của một loại trong tổ hợp ('' khi không có), vd. `code($options, 'size')` = "M".
     *
     * @param  list<array{type?: string, name?: string, code?: string, value?: string}>  $options
     */
    public static function code(array $options, string $type): string
    {
        foreach ($options as $option) {
            if (($option['type'] ?? null) === $type) {
                return (string) ($option['code'] ?? '');
            }
        }

        return '';
    }
}
