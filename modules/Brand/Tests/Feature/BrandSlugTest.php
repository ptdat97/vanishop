<?php

use Illuminate\Support\Facades\Validator;
use Modules\Brand\Application\Validation\BrandSlug;

function slugPasses(string $slug): bool
{
    return Validator::make(['slug' => $slug], ['slug' => [new BrandSlug]])->passes();
}

it('chấp nhận slug hợp lệ', function () {
    expect(slugPasses('lumiere'))->toBeTrue()
        ->and(slugPasses('urbanx-kids'))->toBeTrue();
});

it('từ chối slug sai định dạng', function (string $slug) {
    expect(slugPasses($slug))->toBeFalse();
})->with(['Lumiere', 'lu mi', 'lumière', '-lumiere', 'a/b', str_repeat('a', 65)]);

it('từ chối slug trùng đường dẫn dành riêng, kể cả đường dẫn Admin', function () {
    config(['vanishop.admin.path' => 'quan-tri-7f3k']);

    expect(slugPasses('api'))->toBeFalse()
        ->and(slugPasses('tai-khoan'))->toBeFalse()
        ->and(slugPasses('quan-tri-7f3k'))->toBeFalse();
});
