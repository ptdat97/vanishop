<?php

use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    C::store();
    $this->style = Style::query()->sole();
    $this->category = T::seed(fn () => Category::factory()->create(['slug' => 'dam-vay']));
    T::seed(function () {
        $this->style->syncTranslations([
            'vi' => ['name' => 'Đầm lụa'],
            'en' => ['name' => 'Silk dress', 'slug' => 'silk-dress'],
        ]);
        $this->category->syncTranslations(['vi' => ['name' => 'Đầm váy'], 'en' => ['name' => 'Dresses', 'slug' => 'dresses']]);
        $this->style->categories()->attach($this->category->id);
    });
});

it('trang tiếng Anh dùng slug riêng; slug gốc dưới /en chuyển hướng 301; tiếng Việt chỉ nhận slug gốc', function () {
    $this->get('/en/products/silk-dress')->assertOk()->assertSee('Silk dress');
    $this->get("/en/products/{$this->style->slug}")->assertRedirect(url('/en/products/silk-dress'))->assertStatus(301);
    $this->get("/san-pham/{$this->style->slug}")->assertOk();
    $this->get('/san-pham/silk-dress')->assertNotFound();

    $this->get('/en/category/dresses')->assertOk()->assertSee(url('/en/products/silk-dress'), false);
    $this->get('/en/category/dam-vay?sort=newest')->assertStatus(301)->assertRedirect(url('/en/category/dresses?sort=newest'));
    $this->get('/danh-muc/dam-vay')->assertOk()->assertSee(url("/san-pham/{$this->style->slug}"), false);
});

it('sản phẩm chưa có slug riêng: /en dùng slug gốc, không chuyển hướng', function () {
    T::seed(fn () => $this->style->syncTranslations(['vi' => ['name' => 'Đầm lụa'], 'en' => ['name' => 'Silk dress', 'slug' => null]]));

    $this->get("/en/products/{$this->style->slug}")->assertOk();
});

it('Admin: slug riêng không trùng slug riêng hay slug gốc của đối tượng khác; slug bản tiếng Việt bị bỏ', function () {
    $other = T::product(null, ['name' => 'Áo']);
    $this->actingAs(T::staff(), 'staff');
    $payload = fn (array $en): array => [
        'style_code' => $other->style_code, 'slug' => $other->slug, 'status' => 'active', 'lock_version' => (int) $other->fresh()->lock_version,
        'translations' => ['vi' => ['name' => 'Áo', 'slug' => 'bi-bo-qua'], 'en' => ['name' => 'Shirt', ...$en]],
    ];

    $this->put("/admin/catalog/products/{$other->id}", $payload(['slug' => 'silk-dress']))->assertSessionHasErrors('translations.en.slug');
    $this->put("/admin/catalog/products/{$other->id}", $payload(['slug' => $this->style->slug]))->assertSessionHasErrors('translations.en.slug');
    $this->put("/admin/catalog/products/{$other->id}", $payload(['slug' => 'Bad Slug']))->assertSessionHasErrors('translations.en.slug');
    $this->put("/admin/catalog/products/{$other->id}", $payload(['slug' => 'linen-shirt']))->assertSessionHasNoErrors();

    expect($other->fresh()->load('translations')->slugs())->toBe(['vi' => $other->slug, 'en' => 'linen-shirt']);
});
