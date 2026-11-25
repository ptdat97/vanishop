<?php

declare(strict_types=1);

namespace Modules\Storefront\Testing;

use Closure;
use Modules\Extension\Facades\Hook;
use Modules\Storefront\Contracts\BuilderSurface;
use Modules\Storefront\Contracts\Data\BlockContext;
use Modules\Storefront\Contracts\Data\BuilderZone;
use Modules\Storefront\Contracts\ScopedBuilderSurface;

/**
 * Contract test cho BuilderSurface: mã ổn định (dùng trong URL Admin) + nhãn; zones() là BuilderZone không trùng khoá;
 * vùng slot trỏ tới slot đã khai báo `builder: true` (Core hoặc hooks.php của plugin). Surface theo phạm vi
 * ({@see ScopedBuilderSurface}): trang không mang ngữ cảnh đối tượng không có phạm vi, phạm vi lạ không mô tả được.
 *
 *   BuilderSurfaceContract::define('vani.lookbook', fn () => app(LookbookSurface::class));
 */
final class BuilderSurfaceContract
{
    /**
     * @param  Closure(): BuilderSurface  $surface
     */
    public static function define(string $label, Closure $surface): void
    {
        describe("BuilderSurface contract: {$label}", function () use ($surface): void {
            it('có mã ổn định và nhãn', fn () => expect($surface()->key())->toMatch('/^[a-z][a-z0-9_-]{0,39}$/')->and($surface()->label())->not->toBe(''));

            it('zones là BuilderZone, khoá không trùng, có ít nhất một vùng', function () use ($surface): void {
                $zones = $surface()->zones();
                expect($zones)->not->toBe([])->each()->toBeInstanceOf(BuilderZone::class);
                expect(array_unique(array_map(fn (BuilderZone $zone): string => $zone->key, $zones)))->toHaveCount(count($zones));
            });

            it('vùng slot gắn vào slot nhận block', function () use ($surface): void {
                $slots = array_filter(array_map(fn (BuilderZone $zone): ?string => $zone->slot, $surface()->zones()));
                $rejected = array_values(array_filter($slots, fn (string $slot): bool => ! Hook::acceptsBlocks($slot)));

                expect($rejected)->toBe([], 'Slot chưa khai báo builder: true: '.implode(', ', $rejected));
            });

            it('surface theo phạm vi: ngữ cảnh trống không có phạm vi, phạm vi lạ trả null', function () use ($surface): void {
                $definition = $surface();
                if (! $definition instanceof ScopedBuilderSurface) {
                    expect($definition)->toBeInstanceOf(BuilderSurface::class);

                    return;
                }
                expect($definition->scopeOf(new BlockContext('vani.storefront.home.main')))->toBeNull()
                    ->and($definition->describeScope('zz-khong-ton-tai-0'))->toBeNull();
            });
        });
    }
}
