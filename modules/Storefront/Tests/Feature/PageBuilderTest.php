<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Contracts\Extensions;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Storefront\Application\Blocks\BrandGridBlock;
use Modules\Storefront\Application\Blocks\HeroBlock;
use Modules\Storefront\Application\Blocks\ProductGridBlock;
use Modules\Storefront\Application\Blocks\RichTextBlock;
use Modules\Storefront\Contracts\StorefrontBlock;
use Modules\Storefront\Testing\StorefrontBlockContract;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

StorefrontBlockContract::define('core hero', fn () => app(HeroBlock::class), ['title' => 'Hè 2026', 'link_url' => '/thuong-hieu', 'link_label' => 'Xem']);
StorefrontBlockContract::define('core product_grid', fn () => app(ProductGridBlock::class), ['source' => 'newest', 'limit' => 4]);
StorefrontBlockContract::define('core brand_grid', fn () => app(BrandGridBlock::class), ['title' => 'Thương hiệu']);
StorefrontBlockContract::define('core rich_text', fn () => app(RichTextBlock::class), ['body' => "Dòng 1\nDòng 2"]);

beforeEach(function () {
    ['brand' => $this->brand, 's' => $this->s] = C::store();
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'storefront.manage'])->create(), 'staff');
    $this->url = '/admin/storefront/home-blocks';
});

it('chưa cấu hình → trang chủ mặc định; cấu hình khối → trang chủ render đúng thứ tự, escape nội dung, chặn link javascript:', function () {
    $this->get('/')->assertOk()->assertSee('Sản phẩm mới');

    $this->put($this->url, ['blocks' => [
        ['type' => 'hero', 'config' => ['title' => 'Bộ sưu tập Hè', 'link_label' => 'Mua ngay', 'link_url' => 'javascript:alert(1)']],
        ['type' => 'rich_text', 'config' => ['body' => '<script>alert(1)</script>Miễn phí giao']],
        ['type' => 'product_grid', 'config' => ['title' => 'Bán chạy', 'source' => 'brand', 'slug' => $this->brand->slug, 'limit' => 4]],
    ]])->assertSessionHasNoErrors();

    $html = $this->get('/')->assertOk()->assertDontSee('Sản phẩm mới')->getContent();
    expect($html)->toContain('Bộ sưu tập Hè')->toContain('Đầm lụa')->toContain('&lt;script&gt;')
        ->not->toContain('javascript:alert')
        ->and(strpos($html, 'data-block="hero"'))->toBeLessThan(strpos($html, 'data-block="product_grid"'))
        ->and(AuditLog::query()->where('action', 'storefront.home_blocks_updated')->exists())->toBeTrue();
});

it('validate theo fields của từng loại; loại không có bị từ chối; dùng lại mặc định', function () {
    $this->put($this->url, ['blocks' => [['type' => 'hero', 'config' => []]]])->assertSessionHasErrors('blocks.0.config.title');
    $this->put($this->url, ['blocks' => [['type' => 'khong_co', 'config' => []]]])->assertSessionHasErrors('blocks.0.type');

    $this->put($this->url, ['blocks' => [['type' => 'brand_grid', 'config' => ['title' => 'TH', 'la' => 'x']]]])->assertSessionHasNoErrors();
    $this->get($this->url)->assertInertia(fn (Assert $page) => $page->component('Storefront::Design/HomeBlocks')
        ->where('blocks', [['type' => 'brand_grid', 'config' => ['title' => 'TH']]])->where('configured', true));

    $this->delete($this->url)->assertSessionHasNoErrors();
    $this->get('/')->assertSee('Sản phẩm mới');
});

it('khối lỗi khi render bị bỏ, khối khác vẫn hiện; không có quyền → 403', function () {
    $broken = new class implements StorefrontBlock
    {
        public function type(): string
        {
            return 'broken';
        }

        public function label(): string
        {
            return 'Hỏng';
        }

        public function fields(): array
        {
            return [];
        }

        public function resolve(array $config, string $locale): array
        {
            throw new RuntimeException('plugin hỏng');
        }

        public function view(): string
        {
            return 'theme::blocks.rich_text';
        }
    };
    app()->instance($broken::class, $broken);
    app(Extensions::class)->contribute(StorefrontBlock::TAG, $broken::class, 'vani.cod');

    $this->put($this->url, ['blocks' => [['type' => 'broken', 'config' => []], ['type' => 'rich_text', 'config' => ['body' => 'Còn hiện']]]])->assertSessionHasNoErrors();
    $this->get('/')->assertOk()->assertSee('Còn hiện');

    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access'])->create(), 'staff');
    $this->get($this->url)->assertForbidden();
});
