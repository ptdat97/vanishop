<?php

declare(strict_types=1);

namespace Modules\Notification\Domain;

/**
 * Thay `{{ ten_bien }}` bằng giá trị; biến không có → chuỗi rỗng. Không có logic/điều kiện trong template
 * (R10: không đặt nghiệp vụ trong template).
 */
final class TemplateRenderer
{
    /**
     * @param  array<string, scalar|null>  $variables
     */
    public static function render(?string $template, array $variables): ?string
    {
        if ($template === null) {
            return null;
        }

        return preg_replace_callback(
            '/\{\{\s*([a-z0-9_]+)\s*\}\}/i',
            fn (array $match): string => (string) ($variables[$match[1]] ?? ''),
            $template,
        ) ?? $template;
    }

    /**
     * Render mọi chuỗi trong mảng lồng (tham số riêng của kênh).
     *
     * @param  array<array-key, mixed>  $data
     * @param  array<string, scalar|null>  $variables
     * @return array<array-key, mixed>
     */
    public static function renderArray(array $data, array $variables): array
    {
        foreach ($data as $key => $value) {
            $data[$key] = match (true) {
                is_array($value) => self::renderArray($value, $variables),
                is_string($value) => self::render($value, $variables),
                default => $value,
            };
        }

        return $data;
    }

    /**
     * @return list<string>
     */
    public static function variablesIn(string $template): array
    {
        preg_match_all('/\{\{\s*([a-z0-9_]+)\s*\}\}/i', $template, $matches);

        return array_values(array_unique($matches[1]));
    }
}
