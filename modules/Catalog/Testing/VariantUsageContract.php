<?php

declare(strict_types=1);

namespace Modules\Catalog\Testing;

use Closure;
use Modules\Catalog\Contracts\VariantUsage;

/**
 * Contract test cho VariantUsage. Kiểm tra: mã ổn định; danh sách rỗng → rỗng; chỉ trả SKU được hỏi, kiểu int, không trùng;
 * `release` với danh sách rỗng hoặc SKU không phát sinh không lỗi.
 */
final class VariantUsageContract
{
    /**
     * @param  Closure(): VariantUsage  $usage
     * @param  Closure(): list<int>  $variantIds  SKU có thật trong DB của test
     */
    public static function define(string $label, Closure $usage, Closure $variantIds): void
    {
        describe("VariantUsage contract: {$label}", function () use ($usage, $variantIds): void {
            it('có mã ổn định', fn () => expect($usage()->code())->toMatch('/^[a-z][a-z0-9_.-]*$/'));

            it('chỉ trả SKU được hỏi, int, không trùng; release không lỗi', function () use ($usage, $variantIds): void {
                $ids = $variantIds();
                expect($usage()->used([]))->toBe([]);
                $used = $usage()->used($ids);
                expect(array_diff($used, $ids))->toBe([])
                    ->and(array_values(array_unique($used)))->toBe($used);
                foreach ($used as $id) {
                    expect($id)->toBeInt();
                }
                $usage()->release([]);
                $usage()->release(array_values(array_diff($ids, $used)));
            });
        });
    }
}
