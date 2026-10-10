<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

/**
 * Ngôn ngữ của cửa hàng (`vanishop.locale.default|supported`). Ngôn ngữ mặc định là dự phòng của bản dịch, mẫu thông
 * báo và dữ liệu nhập — Core không ghi cứng mã ngôn ngữ nào.
 */
final class StoreLocale
{
    public static function default(): string
    {
        return (string) config('vanishop.locale.default', config('app.fallback_locale'));
    }

    /**
     * @return list<string>
     */
    public static function supported(): array
    {
        $supported = array_values(array_map('strval', (array) config('vanishop.locale.supported', [])));

        return in_array(self::default(), $supported, true) ? $supported : [self::default(), ...$supported];
    }

    /**
     * Ngôn ngữ cho giao diện (tab bản dịch), mặc định đứng đầu.
     *
     * @return list<array{code: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (string $code): array => ['code' => $code, 'label' => (string) config("vanishop.locale.labels.{$code}", $code)], self::supported());
    }

    public static function isSupported(string $locale): bool
    {
        return in_array($locale, self::supported(), true);
    }
}
