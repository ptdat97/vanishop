<?php

declare(strict_types=1);

namespace Modules\Storefront\Testing;

use Closure;
use Modules\Storefront\Contracts\LinkType;

/**
 * Contract test cho LinkType: mã ổn định + nhãn; giá trị rác bị từ chối; phân giải theo lô trả URL + nhãn cho đối
 * tượng đang hiển thị và bỏ đối tượng không tồn tại/đã ẩn; tìm kiếm (nếu có) trả giá trị phân giải lại được.
 *
 *   LinkTypeContract::define('vani.cms page', fn () => app(PageLinkType::class), visible: fn () => (string) cmsPage()->id,
 *       hidden: fn () => (string) cmsPage(['status' => 'draft', 'slug' => 'nhap'])->id);
 *
 * @param  Closure(): string  $visible  tạo đối tượng đang hiển thị, trả giá trị link
 * @param  (Closure(): string)|null  $hidden  tạo đối tượng không hiển thị (đã ẩn/nháp); null nếu loại không có khái niệm ẩn
 */
final class LinkTypeContract
{
    public static function define(string $label, Closure $type, Closure $visible, ?Closure $hidden = null, string $missing = '999999'): void
    {
        describe("LinkType contract: {$label}", function () use ($type, $visible, $hidden, $missing): void {
            it('có mã ổn định và nhãn', function () use ($type): void {
                $instance = $type();
                expect($instance)->toBeInstanceOf(LinkType::class)
                    ->and($instance->type())->toMatch('/^[a-z][a-z0-9_]*$/')
                    ->and($instance->label())->not->toBe('');
            });

            it('từ chối giá trị rỗng và javascript:', function () use ($type): void {
                expect($type()->accepts(''))->toBeFalse()->and($type()->accepts('javascript:alert(1)'))->toBeFalse();
            });

            it('phân giải theo lô: có URL + nhãn cho đối tượng hiển thị, bỏ giá trị không tồn tại', function () use ($type, $visible, $missing): void {
                $value = $visible();
                $resolved = $type()->resolve([$value, $missing], 'vi');

                expect($type()->accepts($value))->toBeTrue()
                    ->and($resolved)->toHaveKey($value)
                    ->and(array_key_exists($missing, $resolved))->toBeFalse()
                    ->and($resolved[$value]['url'])->toMatch('#^(https?://|/|mailto:|tel:)#')
                    ->and($resolved[$value]['label'])->not->toBe('');
            });

            if ($hidden !== null) {
                it('đối tượng không hiển thị thì không phân giải được', function () use ($type, $hidden): void {
                    $value = $hidden();

                    expect($type()->resolve([$value], 'vi'))->not->toHaveKey($value);
                });
            }

            it('tìm kiếm trả giá trị phân giải được (loại có tìm kiếm)', function () use ($type, $visible): void {
                $instance = $type();
                $value = $visible();
                if (! $instance->searchable()) {
                    expect($instance->search('a', 'vi'))->toBe([]);

                    return;
                }
                $results = $instance->search('', 'vi');

                expect($results)->toBeArray()
                    ->and(count($results))->toBeGreaterThan(0)
                    ->and(array_column($results, 'value'))->toContain($value)
                    ->and($results[0])->toHaveKeys(['value', 'label']);
            });
        });
    }
}
