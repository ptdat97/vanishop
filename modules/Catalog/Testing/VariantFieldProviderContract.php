<?php

declare(strict_types=1);

namespace Modules\Catalog\Testing;

use Closure;
use Modules\Catalog\Contracts\VariantFieldProvider;
use Modules\Extension\Contracts\Data\FieldDefinition;

/**
 * Contract test cho VariantFieldProvider (docs/03-domains/variant-matrix.md §3.3) — plugin góp cột vào ma trận biến thể
 * dùng lại trong test của mình:
 *
 *   VariantFieldProviderContract::define('vani.size-chart', fn () => app(MeasureFields::class), fn (): array => [$styleId, $variantIds]);
 *
 * Kiểm: khoá/nhãn hợp lệ, trường có khoá duy nhất; `values()` chỉ trả SKU được hỏi và trường đã khai; lưu giá trị mẫu hợp lệ
 * theo loại trường rồi đọc lại được; `storefront()` chỉ trả SKU được hỏi.
 */
final class VariantFieldProviderContract
{
    /**
     * @param  Closure(): VariantFieldProvider  $provider
     * @param  Closure(): array{0: int, 1: list<int>}  $product  dựng một sản phẩm có SKU, trả [style id, variant ids]
     */
    public static function define(string $label, Closure $provider, Closure $product): void
    {
        describe("VariantFieldProvider contract: {$label}", function () use ($provider, $product): void {
            it('khoá, nhãn, quyền và trường hợp lệ; khoá trường duy nhất', function () use ($provider, $product): void {
                $product();
                $instance = $provider();
                $keys = array_map(fn (FieldDefinition $field): string => $field->key, $instance->fields());

                expect($instance->code())->toMatch('/^[a-z0-9][a-z0-9._-]*$/')
                    ->and($instance->label())->not->toBe('')
                    ->and($instance->permission() === null || $instance->permission() !== '')->toBeTrue()
                    ->and($keys)->not->toBeEmpty()
                    ->and(array_unique($keys))->toHaveCount(count($keys))
                    ->and(array_filter($keys, fn (string $key): bool => preg_match('/^[a-z0-9_]+$/', $key) !== 1))->toBe([]);
            });

            it('lưu giá trị mẫu theo loại trường rồi đọc lại; chỉ trả SKU được hỏi', function () use ($provider, $product): void {
                [$styleId, $variantIds] = $product();
                $instance = $provider();
                $sample = [];
                foreach ($instance->fields() as $index => $field) {
                    $sample[$field->key] = match ($field->type) {
                        'int' => 1000 * ($index + 1), // tăng dần: trường so sánh (vd. giá gốc > giá bán) vẫn hợp lệ
                        'bool' => true,
                        'select' => (string) array_key_first($field->options),
                        default => 'mẫu',
                    };
                }
                $first = $variantIds[0];

                $errors = $instance->save($styleId, [$first => $sample]);
                $values = $instance->values($styleId, [$first]);

                expect(array_keys($values))->each->toBeIn([$first]);
                expect($errors)->not->toHaveKey($first, 'Giá trị mẫu hợp lệ theo loại trường bị từ chối: '.($errors[$first] ?? ''))
                    ->and(array_intersect_key($values[$first] ?? [], $sample))->toEqual($sample);
                expect(array_diff(array_keys($values[$first] ?? []), array_keys($sample)))->toBe([])
                    ->and(array_diff(array_keys($instance->storefront([$first])), [$first]))->toBe([]);
            });
        });
    }
}
