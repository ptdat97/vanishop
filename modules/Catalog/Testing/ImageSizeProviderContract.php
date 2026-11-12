<?php

declare(strict_types=1);

namespace Modules\Catalog\Testing;

use Closure;
use Modules\Catalog\Contracts\Data\ImageSize;
use Modules\Catalog\Contracts\ImageSizeProvider;

/**
 * Contract test cho ImageSizeProvider (0.3.87): danh sách ImageSize, tên không trùng và có tiền tố riêng của plugin
 * (`<tiền tố>.<tên>`), kết quả ổn định giữa các lần gọi (khoá file cache không đổi ngẫu nhiên).
 */
final class ImageSizeProviderContract
{
    /**
     * @param  Closure(): ImageSizeProvider  $provider
     */
    public static function define(string $label, Closure $provider): void
    {
        describe("ImageSizeProvider contract: {$label}", function () use ($provider): void {
            it('trả kích thước hợp lệ, tên có tiền tố và không trùng, ổn định', function () use ($provider): void {
                $sizes = $provider()->sizes();

                expect($sizes)->not->toBeEmpty();
                $names = array_map(fn (ImageSize $size): string => $size->name, $sizes);
                expect(array_unique($names))->toHaveCount(count($names))
                    ->and(array_filter($names, fn (string $name): bool => ! str_contains($name, '.')))->toBe([])
                    ->and(array_map(fn (ImageSize $size): array => $size->toArray(), $provider()->sizes()))->toBe(array_map(fn (ImageSize $size): array => $size->toArray(), $sizes));
            });
        });
    }
}
