<?php

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Contracts\Cache\Repository as CacheContract;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Hooks\HookManager;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;
use Modules\Shared\Application\PageCache\BatchedPageCacheInvalidator;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Contracts\PageCacheInvalidator;
use Modules\Shared\Events\PageCacheTagsInvalidated;
use Modules\Storefront\Application\PageCache\PageCacheStore;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Cache trang trong các tình huống vận hành thật (ADR-051): bật plugin, deploy, plugin lỗi khi render, query lạ, http/https,
| store cache lỗi, lô xoá lỗi giữa chừng, sản phẩm hẹn giờ hiện.
*/

beforeEach(function () {
    config(['vanishop.storefront.page_cache.mode' => 'app']);
    ['s' => $this->s, 'brand' => $this->brand] = C::store();
    $this->product = '/san-pham/'.$this->s->product->slug;
    Cache::put(BatchedPageCacheInvalidator::PROCESSED_AT_KEY, time(), 3600);
    $this->artisan('vani:page-cache:invalidate')->assertSuccessful();
});

it('bật plugin: cache trang xoá ngay (khối/slot của plugin hiện luôn, không chờ hết hạn)', function () {
    $this->get('/thuong-hieu')->assertHeader('X-Vani-Cache', 'MISS');
    $this->get('/thuong-hieu')->assertHeader('X-Vani-Cache', 'HIT');

    app(CurrentContext::class)->runAs(ContextScope::system('test'), function (): void {
        app(PluginManager::class)->install('vani.wishlist');
        app(PluginManager::class)->enable('vani.wishlist');
    });

    $this->get('/thuong-hieu')->assertHeader('X-Vani-Cache', 'MISS');
});

it('lệnh deploy (optimize, view:clear…) chạy xong thì xoá ngay cache trang; lệnh lỗi hoặc lệnh khác thì không', function () {
    // Chạy từ CLI (deploy) console kernel phát CommandFinished; Artisan::call trong test không phát nên phát trực tiếp.
    $finished = fn (string $command, int $exitCode) => event(new CommandFinished($command, new ArrayInput([]), new NullOutput, $exitCode));
    $this->get('/thuong-hieu');

    $finished('route:list', 0);
    $finished('optimize', 1);
    $this->get('/thuong-hieu')->assertHeader('X-Vani-Cache', 'HIT');

    $finished('optimize', 0);
    $this->get('/thuong-hieu')->assertHeader('X-Vani-Cache', 'MISS');
});

it('plugin lỗi khi render (khối bị bỏ qua): trang vẫn 200 nhưng không lưu bản thiếu khối', function () {
    app(HookManager::class)->onSlot('vani.storefront.pdp.after_price', fn (): array => throw new RuntimeException('plugin hỏng'), pluginId: 'vani.cod');

    $this->get($this->product)->assertOk()->assertHeader('X-Vani-Cache', 'BYPASS')->assertHeader('X-Vani-Cache-Reason', 'plugin_error');
    $this->get($this->product)->assertOk()->assertHeader('X-Vani-Cache', 'BYPASS');
});

it('query lạ không tạo bản cache; tham số theo dõi không lọt vào HTML dùng chung', function () {
    $this->get('/thuong-hieu?x=POISON123')->assertOk()->assertHeader('X-Vani-Cache-Reason', 'query');

    $html = $this->get('/thuong-hieu?utm_source=TRACK123')->assertHeader('X-Vani-Cache', 'MISS')->getContent();
    expect($html)->not->toContain('TRACK123');
    $this->get('/thuong-hieu')->assertHeader('X-Vani-Cache', 'HIT');
});

it('http và https không dùng chung bản cache (URL tuyệt đối khác scheme)', function () {
    $this->get('https://localhost/thuong-hieu')->assertHeader('X-Vani-Cache', 'MISS');

    $this->get('http://localhost/thuong-hieu')->assertHeader('X-Vani-Cache', 'MISS');
    $this->get('https://localhost/thuong-hieu')->assertHeader('X-Vani-Cache', 'HIT');
});

it('store cache lỗi (Redis mất kết nối): trang vẫn phục vụ, không cache', function () {
    $broken = new Repository(new class extends ArrayStore
    {
        public function get($key): mixed
        {
            throw new RuntimeException('Connection refused');
        }

        public function many(array $keys): array
        {
            throw new RuntimeException('Connection refused');
        }
    });
    app()->when(PageCacheStore::class)->needs(CacheContract::class)->give(fn () => $broken);

    $this->get($this->product)->assertOk()->assertHeader('X-Vani-Cache', 'BYPASS')->assertHeader('X-Vani-Cache-Reason', 'cache_error')
        ->assertSee($this->s->product->translate('name', 'vi'));
});

it('lô xoá lỗi giữa chừng (plugin CDN lỗi): tag còn trong hàng đợi, lô sau xoá lại', function () {
    $this->get($this->product);
    app(PageCacheInvalidator::class)->invalidate('product:'.$this->s->product_id);
    $failing = true;
    Event::listen(PageCacheTagsInvalidated::class, function () use (&$failing): void {
        if ($failing) {
            throw new RuntimeException('CDN không phản hồi');
        }
    });

    expect(fn () => app(BatchedPageCacheInvalidator::class)->process())->toThrow(RuntimeException::class)
        ->and(app(BatchedPageCacheInvalidator::class)->pending())->toContain('product:'.$this->s->product_id);

    $failing = false;
    app(BatchedPageCacheInvalidator::class)->process();
    expect(app(BatchedPageCacheInvalidator::class)->pending())->toBe([]);
    $this->get($this->product)->assertHeader('X-Vani-Cache', 'MISS');
});

it('sản phẩm hẹn giờ hiện: trang thương hiệu hết hạn đúng lúc sản phẩm hiện', function () {
    [$variant] = P::variants(T::product($this->brand->id, ['name' => 'Áo hẹn giờ', 'slug' => 'ao-hen-gio', 'published_from' => now()->addMinutes(10)->toDateTimeImmutable()]), ['S']);
    P::priceList(['code' => 'base-hen-gio'], [$variant->id => [150_000]]);
    $this->artisan('vani:page-cache:invalidate')->assertSuccessful();
    $page = '/thuong-hieu/'.$this->brand->slug;
    $this->get($page)->assertDontSee('Áo hẹn giờ');
    $this->get($page)->assertHeader('X-Vani-Cache', 'HIT');

    $this->travel(11)->minutes();
    Cache::put(BatchedPageCacheInvalidator::PROCESSED_AT_KEY, time(), 3600);

    $this->get($page)->assertHeader('X-Vani-Cache', 'MISS')->assertSee('Áo hẹn giờ');
});
