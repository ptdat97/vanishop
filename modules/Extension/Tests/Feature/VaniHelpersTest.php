<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Application\Products\StyleColorService;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Contracts\Customers;
use Modules\Extension\Application\Plugins\PluginAssets;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Tests\Fixtures\FixturePlugins;
use Modules\Shared\Context\Actor;
use Modules\Shared\Context\ActorType;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Support\MoneyFormatter;
use Modules\Tenancy\Contracts\Settings;

require_once __DIR__.'/../Fixtures/FixturePlugins.php';
require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

describe('tài nguyên tĩnh plugin', function () {
    beforeEach(function () {
        $this->root = FixturePlugins::install(['Widget' => ['id' => 'fixture.widget', 'name' => ['vi' => 'Tiện ích', 'en' => 'Widget'], 'assets' => 'public', 'author' => 'Lan']]);
        File::ensureDirectoryExists("{$this->root}/Widget/public/css");
        File::put("{$this->root}/Widget/public/css/widget.css", 'body{color:red}');
        File::put("{$this->root}/Widget/public/evil.php", '<?php echo 1;');
        $this->public = storage_path('framework/testing/public-plugins-'.uniqid());
        app()->instance(PluginAssets::class, new PluginAssets($this->public, 'https://shop.test/plugins'));
        app(CurrentContext::class)->set(ContextScope::system('test'));
        $this->plugins = app(PluginManager::class);
    });

    afterEach(function () {
        File::deleteDirectory($this->root);
        File::deleteDirectory($this->public);
    });

    it('bật plugin → publish tên file có hash, không publish PHP; vani_asset trả URL; gỡ thì xoá', function () {
        $this->plugins->install('fixture.widget');
        expect(vani_asset('fixture.widget', 'css/widget.css'))->toBe('');

        $this->plugins->enable('fixture.widget');
        $hash = substr(hash('sha256', 'body{color:red}'), 0, 10);
        expect(vani_asset('fixture.widget', 'css/widget.css'))->toBe("https://shop.test/plugins/fixture.widget/css/widget.{$hash}.css")
            ->and(File::get("{$this->public}/fixture.widget/css/widget.{$hash}.css"))->toBe('body{color:red}')
            ->and(File::glob("{$this->public}/fixture.widget/*.php"))->toBe([])
            ->and(vani_asset('fixture.widget', 'evil.php'))->toBe('')
            ->and(vani_plugin('fixture.widget'))->toMatchObject(['id' => 'fixture.widget', 'name' => 'Tiện ích', 'version' => '1.0.0', 'enabled' => true, 'author' => 'Lan'])
            ->and(vani_plugin('khong.co'))->toBeNull();

        $this->plugins->disable('fixture.widget');
        expect(vani_plugin('fixture.widget')->enabled)->toBeFalse();
        $this->plugins->uninstall('fixture.widget');
        expect(is_dir("{$this->public}/fixture.widget"))->toBeFalse();
    });

    it('release mới chưa có public/plugins: vani:plugin:cache publish lại cho plugin được nạp, xoá thư mục plugin khác', function () {
        $this->plugins->install('fixture.widget');
        $this->plugins->enable('fixture.widget');
        File::deleteDirectory($this->public);
        File::ensureDirectoryExists("{$this->public}/fixture.cu");

        $this->artisan('vani:plugin:cache')->assertSuccessful();

        expect(vani_asset('fixture.widget', 'css/widget.css'))->not->toBe('')
            ->and(is_dir("{$this->public}/fixture.cu"))->toBeFalse();
    });
});

it('vani_setting, vani_money, vani_locale, vani_admin_url', function () {
    app(CurrentContext::class)->set(ContextScope::system('test'));
    app(Settings::class)->set('core', 'storefront.per_page', 36);
    app(CurrentContext::class)->set(new ContextScope(Actor::guest(), 'en'));

    expect(vani_setting('core', 'storefront.per_page'))->toBe(36)
        ->and(vani_setting('core', 'khong.co', 'mac-dinh'))->toBe('mac-dinh')
        ->and(vani_money(250_000))->toBe(app(MoneyFormatter::class)->formatAmount(250_000, (string) config('vanishop.currency')))
        ->and(vani_locale())->toBe('en')
        ->and(vani_admin_url('dashboard'))->toBe(route('admin.dashboard'))
        ->and(vani_admin_url('admin.dashboard'))->toBe(route('admin.dashboard'));
});

it('vani_customer chỉ trả khách đang đăng nhập (DTO); vani_staff chỉ trả nhân viên', function () {
    app(CurrentContext::class)->set(ContextScope::system('test'));
    $customerId = app(Customers::class)->resolveForCheckout('0912345678', 'Nguyễn Thị Lan', null);

    app(CurrentContext::class)->set(new ContextScope(Actor::guest(), 'vi'));
    expect(vani_customer())->toBeNull()->and(vani_staff())->toBeNull();

    app(CurrentContext::class)->set(new ContextScope(new Actor(ActorType::Customer, $customerId), 'vi'));
    expect(vani_customer()?->id)->toBe($customerId)->and(vani_staff())->toBeNull();

    app(CurrentContext::class)->set(new ContextScope(Actor::staff(7), 'vi'));
    expect(vani_customer())->toBeNull()->and(vani_staff()?->id)->toBe(7);
});

it('vani_image: bản thu nhỏ theo bề rộng; id không có → ảnh thay thế đã cấu hình, chưa cấu hình → chuỗi rỗng', function () {
    Storage::fake('public');
    ['s' => $variant] = C::store();
    app(CurrentContext::class)->runAs(ContextScope::system('test'), fn () => app(StyleColorService::class)->addImages($variant->style, $variant->styleColor, [
        UploadedFile::fake()->image('anh.jpg', 1000, 1000),
    ]));
    $media = $variant->styleColor->gallery()->with('media')->first()->media;

    expect(vani_image($media->id, 400))->toBe($media->url(400))
        ->and(vani_image(999_999, 400))->toBe('')
        ->and(vani_image(null, 400))->toBe('');

    app(CurrentContext::class)->runAs(ContextScope::system('test'), fn () => app(Settings::class)->set('core', 'storefront.placeholder_image', $media->id));
    expect(vani_image(null, 160))->toBe($media->url(160))->and(vani_image(999_999, 160))->toBe($media->url(160));
});
