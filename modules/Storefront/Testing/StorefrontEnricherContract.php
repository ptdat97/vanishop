<?php

declare(strict_types=1);

namespace Modules\Storefront\Testing;

use Closure;
use Modules\Storefront\Contracts\StorefrontEnricher;

/**
 * Contract test cho StorefrontEnricher: tài nguyên hợp lệ; chỉ trả dữ liệu cho phần tử được đưa vào; dữ liệu là
 * mảng giá trị JSON được; danh sách rỗng → rỗng; xác định.
 *
 *   StorefrontEnricherContract::define('vani.reviews', fn () => app(RatingEnricher::class), items: fn () => [['id' => 1, 'slug' => 'a']]);
 */
final class StorefrontEnricherContract
{
    /**
     * @param  Closure(): StorefrontEnricher  $enricher
     * @param  Closure(): list<array<string, mixed>>  $items  phần tử mẫu (có `id`) — có thể tạo dữ liệu trước khi trả
     */
    public static function define(string $label, Closure $enricher, Closure $items): void
    {
        describe("StorefrontEnricher contract: {$label}", function () use ($enricher, $items): void {
            it('khai báo tài nguyên hợp lệ', fn () => expect($enricher()->resource())->toBeIn(StorefrontEnricher::RESOURCES));

            it('chỉ trả dữ liệu cho phần tử được đưa vào, dạng mảng JSON được, xác định', function () use ($enricher, $items): void {
                $input = $items();
                $result = $enricher()->enrich($input, 'vi');
                $ids = array_map(fn (array $item): string => (string) $item['id'], $input);

                expect(array_diff(array_map('strval', array_keys($result)), $ids))->toBe([])
                    ->and($result)->each->toBeArray()
                    ->and(json_encode($result))->not->toBeFalse()
                    ->and($enricher()->enrich($input, 'vi'))->toBe($result);
            });

            it('danh sách rỗng → kết quả rỗng', fn () => expect($enricher()->enrich([], 'vi'))->toBe([]));
        });
    }
}
