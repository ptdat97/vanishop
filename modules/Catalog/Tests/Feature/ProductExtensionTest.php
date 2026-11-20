<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Application\Products\ProductInput;
use Modules\Catalog\Application\Products\ProductService;
use Modules\Catalog\Contracts\Data\ProductDraft;
use Modules\Catalog\Domain\StyleStatus;
use Modules\Catalog\Events\ProductArchived;
use Modules\Catalog\Events\ProductCreated;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Extension\Facades\Hook;

require_once __DIR__.'/CatalogTestHelpers.php';

beforeEach(function () {
    $this->brand = Brand::factory()->create();
});

it('hook vani.product.before_save chặn lưu khi plugin báo lỗi', function () {
    Hook::onValidate('vani.product.before_save', fn (ProductDraft $draft) => str_starts_with($draft->styleCode, 'LM') ? [] : ['Mã phải bắt đầu bằng LM']);

    expect(fn () => T::product($this->brand->id, ['style_code' => 'XX01']))->toThrow(ValidationException::class);
    expect(T::product($this->brand->id, ['style_code' => 'LM01'])->style_code)->toBe('LM01');
});

it('hook vani.product.after_save chạy trong transaction với id sản phẩm', function () {
    $seen = [];
    Hook::onAction('vani.product.after_save', function (int $styleId) use (&$seen) {
        $seen[] = $styleId;
    });

    $style = T::product($this->brand->id);

    expect($seen)->toBe([$style->id]);
});

it('phát event sau commit: tạo sản phẩm → ProductCreated, xoá nháp → ProductArchived', function () {
    $events = [];
    Event::listen([ProductCreated::class, ProductArchived::class], function (object $event) use (&$events): void {
        $events[] = class_basename($event).':'.$event->styleId;
    });

    $style = T::product($this->brand->id, ['status' => 'draft']);
    T::seed(fn () => app(ProductService::class)->deleteDraft($style));

    expect($events)->toBe(["ProductCreated:{$style->id}", "ProductArchived:{$style->id}"]);
});

it('không phát event khi transaction bị rollback', function () {
    Event::fake([ProductCreated::class]);
    Hook::onValidate('vani.product.before_save', fn () => ['chặn']);

    try {
        T::product($this->brand->id);
    } catch (ValidationException) {
    }

    Event::assertNotDispatched(ProductCreated::class);
});

it('lưu trữ sản phẩm phát ProductArchived', function () {
    Event::fake([ProductArchived::class]);
    $style = T::product($this->brand->id);

    T::seed(fn () => app(ProductService::class)->update($style, new ProductInput(
        $style->style_code, $style->slug, StyleStatus::Archived, ['vi' => ['name' => 'X']],
    ), 0));

    Event::assertDispatched(ProductArchived::class, fn (ProductArchived $event) => $event->styleId === $style->id);
});
