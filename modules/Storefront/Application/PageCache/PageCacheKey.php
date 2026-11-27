<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\PageCache;

use Illuminate\Http\Request;
use Modules\Shared\Context\CurrentContext;
use Modules\Storefront\Application\PriceVisibility;
use Modules\Storefront\Application\Theme\Themes;

/**
 * Khoá cache trang (ADR-051 §5): đường dẫn, query đã sắp xếp và bỏ tham số theo dõi, ngôn ngữ, chế độ hiện giá, theme
 * đang dùng, mã bản build (deploy mới thì dùng khoá mới). `null` = không lưu trang này (lý do ở `$reason`).
 */
final class PageCacheKey
{
    private static ?string $build = null;

    public function __construct(
        private readonly CurrentContext $context,
        private readonly PriceVisibility $prices,
        private readonly Themes $themes,
    ) {}

    /**
     * @param-out string|null  $reason
     */
    public function for(Request $request, ?string &$reason = null): ?string
    {
        $query = $this->query($request);
        if (trim((string) ($query['q'] ?? '')) !== '') {
            $reason = 'search';

            return null;
        }
        $filters = array_diff_key($query, array_flip(['page', 'sort']));
        if (self::values($filters) > (int) config('vanishop.storefront.page_cache.max_filter_values', 4)) {
            $reason = 'filters';

            return null;
        }

        return 'vani:page:'.sha1((string) json_encode([
            $request->getHost(),
            $request->getPathInfo(),
            $query,
            $this->context->has() ? $this->context->locale() : null,
            $this->prices->membersOnly(),
            $this->themes->active()->name,
            self::build(),
        ]));
    }

    /**
     * Query đã sắp xếp theo khoá (đệ quy), bỏ tham số theo dõi (`utm_*`, `gclid`, `fbclid`…, cấu hình `ignored_query`).
     *
     * @return array<string, mixed>
     */
    public function query(Request $request): array
    {
        $ignored = (array) config('vanishop.storefront.page_cache.ignored_query', []);
        $query = array_filter($request->query->all(), function (string|int $key) use ($ignored): bool {
            foreach ($ignored as $pattern) {
                if (fnmatch((string) $pattern, (string) $key)) {
                    return false;
                }
            }

            return true;
        }, ARRAY_FILTER_USE_KEY);

        return self::sorted($query);
    }

    /**
     * Phiên bản Core + bản build asset: deploy mới không phục vụ HTML trỏ tới asset cũ.
     */
    public static function build(): string
    {
        if (self::$build === null) {
            $manifest = public_path('build/manifest.json');
            self::$build = config('vanishop.version').'@'.(is_file($manifest) ? (string) filemtime($manifest) : '0');
        }

        return self::$build;
    }

    /**
     * Số giá trị lọc (lá của mảng query lồng nhau).
     *
     * @param  array<string|int, mixed>  $values
     */
    private static function values(array $values): int
    {
        return array_sum(array_map(fn (mixed $value): int => is_array($value) ? self::values($value) : 1, $values));
    }

    /**
     * @param  array<string|int, mixed>  $values
     * @return array<string|int, mixed>
     */
    private static function sorted(array $values): array
    {
        ksort($values);

        return array_map(fn (mixed $value): mixed => is_array($value) ? self::sorted($value) : $value, $values);
    }
}
