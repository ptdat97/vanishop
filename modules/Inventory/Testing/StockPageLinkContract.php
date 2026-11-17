<?php

declare(strict_types=1);

namespace Modules\Inventory\Testing;

use Closure;
use Modules\Inventory\Contracts\StockPageLink;

/**
 * Contract test cho StockPageLink. Kiểm tra: nhãn; đường dẫn null (ẩn) hoặc URL tuyệt đối/tương đối; `managesLocations` bool.
 */
final class StockPageLinkContract
{
    /**
     * @param  Closure(): StockPageLink  $link
     */
    public static function define(string $label, Closure $link): void
    {
        describe("StockPageLink contract: {$label}", function () use ($link): void {
            it('nhãn; URL hợp lệ hoặc null', function () use ($link): void {
                expect($link()->label())->not->toBe('')->and($link()->managesLocations())->toBeBool();
                $url = $link()->url();
                expect($url === null || str_starts_with($url, '/') || str_starts_with($url, 'http'))->toBeTrue();
            });
        });
    }
}
