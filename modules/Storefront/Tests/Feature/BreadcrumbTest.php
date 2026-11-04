<?php

use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;
use Modules\Storefront\View\StructuredData;

/**
 * JSON-LD BreadcrumbList của trang: tìm khối ld+json có @type BreadcrumbList.
 *
 * @return array<string, mixed>|null
 */
function breadcrumbLd(string $html): ?array
{
    preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);
    foreach ($matches[1] as $json) {
        $data = json_decode($json, true);
        if (($data['@type'] ?? null) === 'BreadcrumbList') {
            return $data;
        }
    }

    return null;
}

beforeEach(function () {
    $this->brand = Brand::factory()->create(['slug' => 'lumiere', 'name' => 'Lumière']);
    T::seed(function () {
        $this->women = Category::factory()->create(['slug' => 'nu']);
        $this->women->syncTranslations(['vi' => ['name' => 'Nữ'], 'en' => ['name' => 'Women', 'slug' => 'women']]);
        $this->dresses = Category::factory()->childOf($this->women)->create(['slug' => 'dam']);
        $this->dresses->syncTranslations(['vi' => ['name' => 'Đầm'], 'en' => ['name' => 'Dresses', 'slug' => 'dresses']]);
    });
    $this->product = T::product($this->brand->id, ['name' => 'Đầm lụa', 'slug' => 'dam-lua', 'category_ids' => [$this->dresses->id], 'primary_category_id' => $this->dresses->id]);
    [$variant] = P::variants($this->product, ['S']);
    P::priceList(['code' => 'base'], [$variant->id => [300_000]]);
});

it('PDP: breadcrumb theo danh mục chính + JSON-LD BreadcrumbList, trang hiện tại không có item', function () {
    $html = $this->get('/san-pham/dam-lua')->assertOk()->assertSee('data-vani-breadcrumb', false)->getContent();

    expect(breadcrumbLd($html)['itemListElement'])->toBe([
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Trang chủ', 'item' => url('/')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Nữ', 'item' => url('/danh-muc/nu')],
        ['@type' => 'ListItem', 'position' => 3, 'name' => 'Đầm', 'item' => url('/danh-muc/dam')],
        ['@type' => 'ListItem', 'position' => 4, 'name' => 'Đầm lụa'],
    ]);
});

it('danh mục: tổ tiên từ gốc; bản en dùng tên và URL tiếng Anh', function () {
    $items = breadcrumbLd($this->get('/danh-muc/dam')->assertOk()->getContent())['itemListElement'];
    expect(array_column($items, 'name'))->toBe(['Trang chủ', 'Nữ', 'Đầm'])
        ->and($items[2])->not->toHaveKey('item');

    $items = breadcrumbLd($this->get('/en/category/dresses')->assertOk()->getContent())['itemListElement'];
    expect(array_column($items, 'name'))->toBe(['Home', 'Women', 'Dresses'])
        ->and($items[0]['item'])->toBe(url('/en'))
        ->and($items[1]['item'])->toBe(url('/en/category/women'));
});

it('thương hiệu: Trang chủ → Thương hiệu → tên thương hiệu', function () {
    $items = breadcrumbLd($this->get('/thuong-hieu/lumiere')->assertOk()->getContent())['itemListElement'];

    expect(array_column($items, 'name'))->toBe(['Trang chủ', 'Thương hiệu', 'Lumière'])
        ->and($items[1]['item'])->toBe(url('/thuong-hieu'));
});

it('JSON-LD không thoát được khỏi thẻ script', function () {
    expect(StructuredData::breadcrumbs([['label' => '</script><script>alert(1)</script>', 'url' => null]]))->not->toContain('</script>');
});
