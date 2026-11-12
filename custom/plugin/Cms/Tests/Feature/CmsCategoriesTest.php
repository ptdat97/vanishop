<?php

use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Testing\StorefrontApiContract as Api;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Storefront\Testing\LinkTypeContract;
use Plugin\Cms\CmsServiceProvider;
use Plugin\Cms\Infrastructure\ArticleCategoryLinkType;
use Plugin\Cms\Persistence\Category;
use Plugin\Cms\Persistence\Post;

require_once __DIR__.'/../../../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

LinkTypeContract::define('vani.cms article_category', fn () => app(ArticleCategoryLinkType::class),
    visible: fn () => (string) Category::query()->create(['slug' => 'xu-huong', 'name' => 'Xu hướng'])->id, missing: '999999');

beforeEach(function () {
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install(CmsServiceProvider::ID);
        app(PluginManager::class)->enable(CmsServiceProvider::ID);
    });
    app()->register(CmsServiceProvider::class);
    Cache::flush();
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'cms.view', 'cms.manage'])->create(), 'staff');
    $this->admin = '/admin/plugins/vani-cms';
    $this->post = fn (array $attributes = []) => Post::query()->create([
        'slug' => 'bo-suu-tap-thu', 'title' => 'Bộ sưu tập thu', 'body' => 'Nội dung.', 'status' => 'published', 'published_at' => now()->subDay(), ...$attributes,
    ]);
});

it('Admin: thêm/sửa/xoá chuyên mục; đường dẫn không trùng bài viết (cả hai chiều); xoá chuyên mục giữ bài viết', function () {
    $post = ($this->post)();

    $this->post("{$this->admin}/categories", ['name' => 'Bộ sưu tập thu'])->assertSessionHasErrors('slug');
    $this->post("{$this->admin}/categories", ['name' => 'Xu hướng', 'description' => 'Mặc gì mùa này'])->assertSessionHasNoErrors();
    $category = Category::query()->sole();
    expect($category->slug)->toBe('xu-huong');

    $this->put("{$this->admin}/posts/{$post->id}", ['title' => 'Bài khác', 'slug' => 'xu-huong', 'body' => 'x', 'status' => 'published'])->assertSessionHasErrors('slug');
    $this->get("{$this->admin}/categories")->assertOk()->assertInertia(fn (Assert $page) => $page->component('Cms::Categories/Index')->where('categories.0.name', 'Xu hướng'));

    $post->update(['category_id' => $category->id]);
    $this->delete("{$this->admin}/categories/{$category->id}")->assertSessionHasNoErrors();
    expect($post->fresh()->category_id)->toBeNull()->and(Post::query()->count())->toBe(1);
});

it('storefront: /tin-tuc/{chuyên mục} liệt kê bài của chuyên mục; trang tin tức có thanh chuyên mục; bài hiện chuyên mục', function () {
    $category = Category::query()->create(['slug' => 'xu-huong', 'name' => 'Xu hướng']);
    ($this->post)(['category_id' => $category->id]);
    ($this->post)(['slug' => 'tin-khac', 'title' => 'Tin khác']);

    $this->get('/tin-tuc')->assertOk()->assertSee('data-cms-categories', false)->assertSee('Xu hướng');
    $this->get('/tin-tuc/xu-huong')->assertOk()->assertSee('Bộ sưu tập thu')->assertDontSee('Tin khác');
    $this->get('/tin-tuc/bo-suu-tap-thu')->assertOk()->assertSee('data-cms-post-category', false);
    $this->getJson('/api/storefront/v1/x/vani-cms/posts?category=xu-huong')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.category.slug', 'xu-huong');
    Api::assertResponse($this->getJson('/api/storefront/v1/x/vani-cms/categories'), dirname(__DIR__, 2), '/categories', 'GET')->assertOk()->assertJsonPath('data.0.url', url('/tin-tuc/xu-huong'));
    $this->get('/sitemap.xml')->assertSee(url('/tin-tuc/xu-huong'), false);
});

it('gắn sản phẩm vào bài: tối đa 12, giữ thứ tự; mẫu ẩn bị bỏ khi hiển thị; Admin tìm sản phẩm đang bán', function () {
    ['s' => $variant, 'brand' => $brand] = C::store();
    $product = $variant->style;
    $hidden = CatalogTestHelpers::product($brand->id, ['status' => 'draft']);
    $post = ($this->post)();

    $this->getJson("{$this->admin}/products?q=".urlencode('Đầm'))->assertOk()->assertJsonPath('data.0.id', $product->id);
    $this->put("{$this->admin}/posts/{$post->id}", ['title' => 'Bộ sưu tập thu', 'slug' => 'bo-suu-tap-thu', 'body' => 'x', 'status' => 'published', 'product_ids' => range(1, 13)])
        ->assertSessionHasErrors('product_ids');
    $this->put("{$this->admin}/posts/{$post->id}", ['title' => 'Bộ sưu tập thu', 'slug' => 'bo-suu-tap-thu', 'body' => 'x', 'status' => 'published', 'product_ids' => [$hidden->id, $product->id]])
        ->assertSessionHasNoErrors();
    expect($post->fresh()->product_ids)->toBe([$hidden->id, $product->id]);

    $this->get("{$this->admin}/posts/{$post->id}")->assertInertia(fn (Assert $page) => $page->where('item.products.0.visible', false)->where('item.products.1.id', $product->id));
    $this->get('/tin-tuc/bo-suu-tap-thu')->assertOk()->assertSee('data-cms-post-products', false)->assertSee('Đầm lụa');
    Api::assertResponse($this->getJson('/api/storefront/v1/x/vani-cms/posts/bo-suu-tap-thu'), dirname(__DIR__, 2), '/posts/{slug}', 'GET')->assertJsonCount(1, 'data.products');
});
