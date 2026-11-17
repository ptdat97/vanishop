<?php

declare(strict_types=1);

namespace Modules\Storefront\Testing;

use Closure;
use Modules\Storefront\Contracts\SitemapProvider;

/**
 * Contract test cho SitemapProvider. Kiểm tra: tối đa `$limit` URL tuyệt đối, không trùng; limit 0 → rỗng. Bên gọi chuẩn bị
 * dữ liệu để có ít nhất một URL.
 */
final class SitemapProviderContract
{
    /**
     * @param  Closure(): SitemapProvider  $provider
     * @param  Closure(): void  $seed
     */
    public static function define(string $label, Closure $provider, Closure $seed): void
    {
        describe("SitemapProvider contract: {$label}", function () use ($provider, $seed): void {
            it('tối đa limit URL tuyệt đối, không trùng; limit 0 → rỗng', function () use ($provider, $seed): void {
                $seed();
                $urls = $provider()->urls(1000);
                expect($urls)->not->toBe([])
                    ->and(array_values(array_unique($urls)))->toBe($urls)
                    ->and(count($provider()->urls(1)))->toBeLessThanOrEqual(1)
                    ->and($provider()->urls(0))->toBe([]);
                foreach ($urls as $url) {
                    expect($url)->toMatch('#^https?://#');
                }
            });
        });
    }
}
