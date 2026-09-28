<?php

use Modules\Catalog\Domain\Category\CategoryPath;
use Modules\Catalog\Domain\Category\CategoryTooDeep;

it('dựng path gốc và path con', function () {
    $path = CategoryPath::root(12)->child(57);

    expect($path->toString())->toBe('/12/57/')
        ->and($path->depth())->toBe(2)
        ->and($path->id())->toBe(57)
        ->and(CategoryPath::fromString('/12/57/')->ids)->toBe([12, 57]);
});

it('từ chối path sai định dạng', function (string $raw) {
    CategoryPath::fromString($raw);
})->with(['', '/', '12/57', '/12/a/', '/12//57/'])->throws(InvalidArgumentException::class);

it('kiểm tra quan hệ cây con', function () {
    $root = CategoryPath::fromString('/1/');
    $child = CategoryPath::fromString('/1/2/');

    expect($child->isWithin($root))->toBeTrue()
        ->and($root->isWithin($root))->toBeTrue()
        ->and($root->isWithin($child))->toBeFalse()
        ->and(CategoryPath::fromString('/10/')->isWithin($root))->toBeFalse();
});

it('rebase cây con khi di chuyển', function () {
    $descendant = CategoryPath::fromString('/1/2/3/');

    expect($descendant->rebase(CategoryPath::fromString('/1/2/'), CategoryPath::fromString('/9/2/'))->toString())->toBe('/9/2/3/');
});

it('giới hạn độ sâu tối đa', function () {
    $path = CategoryPath::fromString('/1/2/3/4/5/');

    expect(fn () => $path->child(6))->toThrow(CategoryTooDeep::class)
        ->and(fn () => CategoryPath::fromString('/1/2/3/')->rebase(CategoryPath::fromString('/1/'), CategoryPath::fromString('/7/8/9/1/')))->toThrow(CategoryTooDeep::class);
});
