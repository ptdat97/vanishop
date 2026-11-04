<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Links;

use Modules\Storefront\Contracts\LinkType;

/**
 * URL nhập tay: đường dẫn nội bộ (`/…`), `http(s)://`, `mailto:`, `tel:`. Chặn `javascript:`, `data:` và `//host`
 * (URL không có scheme). Nhãn mặc định là chính URL — nên đặt nhãn ghi đè.
 */
final class UrlLinkType implements LinkType
{
    public function type(): string
    {
        return 'url';
    }

    public function label(): string
    {
        return 'Đường dẫn tuỳ chọn';
    }

    public function searchable(): bool
    {
        return false;
    }

    public function options(): array
    {
        return [];
    }

    public function search(string $query, string $locale, int $limit = 20): array
    {
        return [];
    }

    public function accepts(string $value): bool
    {
        if (strlen($value) > 2048 || preg_match('/[\s<>"\'\x00-\x1f]/', $value) === 1) {
            return false;
        }

        return preg_match('#^/(?!/)#', $value) === 1
            || (preg_match('#^https?://[^/\s]+#i', $value) === 1 && filter_var($value, FILTER_VALIDATE_URL) !== false)
            || preg_match('#^mailto:[^@\s]+@[^@\s]+$#i', $value) === 1
            || preg_match('#^tel:\+?[0-9 ().-]{3,20}$#i', $value) === 1;
    }

    public function resolve(array $values, string $locale): array
    {
        $resolved = [];
        foreach ($values as $value) {
            if ($this->accepts($value)) {
                $resolved[$value] = ['url' => $value, 'label' => $value];
            }
        }

        return $resolved;
    }
}
