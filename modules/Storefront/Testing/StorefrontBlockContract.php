<?php

declare(strict_types=1);

namespace Modules\Storefront\Testing;

use Closure;
use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Storefront\Contracts\StorefrontBlock;

/**
 * Contract test cho StorefrontBlock: mã ổn định + nhãn; fields() là FieldDefinition không trùng khoá; cấu hình mẫu →
 * resolve trả mảng; view tồn tại và render được với dữ liệu đó.
 *
 *   StorefrontBlockContract::define('vani.lookbook', fn () => app(LookbookBlock::class), config: ['title' => 'Hè']);
 */
final class StorefrontBlockContract
{
    /**
     * @param  Closure(): StorefrontBlock  $block
     * @param  array<string, mixed>  $config
     */
    public static function define(string $label, Closure $block, array $config): void
    {
        describe("StorefrontBlock contract: {$label}", function () use ($block, $config): void {
            it('có mã ổn định và nhãn', fn () => expect($block()->type())->toMatch('/^[a-z][a-z0-9_]*$/')->and($block()->label())->not->toBe(''));

            it('fields là FieldDefinition, khoá không trùng', function () use ($block): void {
                $fields = $block()->fields();
                expect($fields)->each->toBeInstanceOf(FieldDefinition::class)
                    ->and(array_unique(array_map(fn (FieldDefinition $field): string => $field->key, $fields)))->toHaveCount(count($fields));
            });

            it('resolve cấu hình mẫu → render được view', function () use ($block, $config): void {
                $instance = $block();
                $data = $instance->resolve($config, 'vi');
                // View dùng namespace theme cần chuỗi theme của request; contract test trỏ theme:: tới vani-base.
                view()->replaceNamespace('theme', [base_path('custom/theme/vani-base/views')]);

                expect($data)->toBeArray()->and(view($instance->view(), $data)->render())->toBeString();
            });
        });
    }
}
