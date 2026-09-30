<?php

declare(strict_types=1);

namespace Modules\Catalog\Testing;

use Closure;
use Modules\Catalog\Contracts\Data\ProductDocument;
use Modules\Catalog\Contracts\Data\ProductSearchQuery;
use Modules\Catalog\Contracts\Data\ProductSearchResult;
use Modules\Catalog\Contracts\SearchProvider;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

/**
 * Contract test cho SearchProvider. Plugin cung cấp `scenario`: tạo dữ liệu (nếu cần) và trả
 * [ProductDocument, ProductSearchQuery tìm thấy tài liệu đó]. Kiểm tra: mã ổn định; index idempotent (lặp lại không
 * nhân bản kết quả); tìm thấy sau khi index; remove idempotent và không còn trong kết quả; total ≥ số id trả về.
 */
final class SearchProviderContract
{
    /**
     * @param  Closure(): SearchProvider  $provider
     * @param  Closure(): array{0: ProductDocument, 1: ProductSearchQuery}  $scenario
     * @param  bool  $removeHidesFromSearch  false với provider đọc thẳng DB (remove là no-op theo thiết kế)
     */
    public static function define(string $label, Closure $provider, Closure $scenario, bool $removeHidesFromSearch = true): void
    {
        describe("SearchProvider contract: {$label}", function () use ($provider, $scenario, $removeHidesFromSearch): void {
            it('có mã ổn định', fn () => expect($provider()->code())->toMatch('/^[a-z][a-z0-9_]*$/'));

            it('index idempotent, tìm thấy; remove idempotent', function () use ($provider, $scenario, $removeHidesFromSearch): void {
                // Provider chạy trong phạm vi request/job; contract test dùng phạm vi hệ thống.
                app(CurrentContext::class)->set(ContextScope::system('search provider contract'));
                [$document, $query] = $scenario();
                $instance = $provider();
                $instance->index($document);
                $instance->index($document);

                $result = $instance->search($query);
                expect($result)->toBeInstanceOf(ProductSearchResult::class)
                    ->and(array_count_values($result->styleIds)[$document->id] ?? 0)->toBe(1)
                    ->and($result->total)->toBeGreaterThanOrEqual(count($result->styleIds));

                $instance->remove($document->id);
                $instance->remove($document->id);
                if ($removeHidesFromSearch) {
                    expect($instance->search($query)->styleIds)->not->toContain($document->id);
                }
            });
        });
    }
}
