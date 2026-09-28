<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Application\Products\ProductInput;
use Modules\Catalog\Application\Products\ProductService;
use Modules\Catalog\Application\Search\SearchManager;
use Modules\Catalog\Contracts\Data\ProductDocument;
use Modules\Catalog\Contracts\Data\ProductDraft;
use Modules\Catalog\Contracts\Data\ProductSearchQuery;
use Modules\Catalog\Contracts\Data\ProductSearchResult;
use Modules\Catalog\Contracts\SearchProvider;
use Modules\Catalog\Domain\StyleStatus;
use Modules\Catalog\Events\ProductArchived;
use Modules\Catalog\Events\ProductCreated;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Extension\Facades\Hook;

require_once __DIR__.'/CatalogTestHelpers.php';

final class RecordingSearchProvider implements SearchProvider
{
    /** @var list<string> */
    public array $calls = [];

    public function code(): string
    {
        return 'recording';
    }

    public function index(ProductDocument $document): void
    {
        $this->calls[] = "index:{$document->id}:{$document->status}";
    }

    public function remove(int $styleId): void
    {
        $this->calls[] = "remove:{$styleId}";
    }

    public function search(ProductSearchQuery $query): ProductSearchResult
    {
        return new ProductSearchResult([], 0);
    }
}

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
    Hook::onAction('vani.product.after_save', function (int $styleId, int $brandId) use (&$seen) {
        $seen[] = [$styleId, $brandId];
    });

    $style = T::product($this->brand->id);

    expect($seen)->toBe([[$style->id, $this->brand->id]]);
});

it('phát event sau commit và đồng bộ chỉ mục qua SearchProvider đang cấu hình', function () {
    $recorder = new RecordingSearchProvider;
    $this->app->instance(RecordingSearchProvider::class, $recorder);
    $this->app->tag([RecordingSearchProvider::class], SearchManager::TAG);
    config(['vanishop.search.provider' => 'recording']);

    $style = T::product($this->brand->id, ['status' => 'draft']);
    T::seed(fn () => app(ProductService::class)->deleteDraft($style));

    expect($recorder->calls)->toBe(["index:{$style->id}:draft", "remove:{$style->id}"]);
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
