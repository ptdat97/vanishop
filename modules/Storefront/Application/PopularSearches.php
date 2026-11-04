<?php

declare(strict_types=1);

namespace Modules\Storefront\Application;

use Modules\Tenancy\Contracts\Settings;

/**
 * Từ khoá tìm kiếm nổi bật (`core`/`storefront.popular_searches`, reference-parity §3.2): mỗi dòng một từ khoá.
 */
final class PopularSearches
{
    public const SETTING = 'storefront.popular_searches';

    public const MAX = 10;

    /**
     * @return list<string>
     */
    public static function from(Settings $settings): array
    {
        $lines = preg_split('/\R/', (string) $settings->get('core', self::SETTING, '')) ?: [];

        return array_slice(array_values(array_unique(array_filter(array_map(fn (string $line): string => mb_substr(trim($line), 0, 60), $lines), fn (string $line): bool => $line !== ''))), 0, self::MAX);
    }
}
