<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Text;

use Normalizer;

/**
 * Chuẩn hoá văn bản tiếng Việt để tìm kiếm: chữ thường, bỏ dấu, "đ" → "d", gộp khoảng trắng.
 * "Áo Sơ Mi Lụa ĐEN" → "ao so mi lua den".
 */
final class VietnameseText
{
    public static function normalize(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = str_replace('đ', 'd', $text);

        $decomposed = Normalizer::normalize($text, Normalizer::FORM_D);
        $text = preg_replace('/\p{Mn}+/u', '', $decomposed === false ? $text : $decomposed) ?? $text;

        $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    /**
     * Các từ khoá đã chuẩn hoá, bỏ trùng.
     *
     * @return list<string>
     */
    public static function tokens(string $text): array
    {
        $normalized = self::normalize($text);

        return $normalized === '' ? [] : array_values(array_unique(explode(' ', $normalized)));
    }
}
