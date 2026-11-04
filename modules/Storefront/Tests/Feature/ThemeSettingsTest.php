<?php

use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Storefront\Application\Theme\Themes;
use Modules\Storefront\Application\Theme\ThemeSettings;
use Modules\Tenancy\Contracts\Settings;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    C::store();
    $this->url = '/admin/storefront/theme-settings';
    $this->settings = fn (array $overrides = []): array => array_replace_recursive([
        'header' => ['announcement' => ['vi' => 'Miễn phí giao hàng từ 500.000', 'en' => ''], 'announcement_url' => 'https://shop.test/khuyen-mai'],
        'footer' => [
            'contact_phone' => '0912 345 678', 'contact_email' => 'cskh@shop.test', 'contact_address' => ['vi' => '12 Lê Lợi, Quận 1', 'en' => '12 Le Loi, District 1'],
            'facebook_url' => 'https://facebook.com/shop', 'payment_methods' => "COD\nChuyển khoản", 'copyright' => ['vi' => '© Cửa hàng Lan', 'en' => '© Lan Store'],
        ],
    ], $overrides);
});

it('Admin: form sinh từ schema của theme; lưu được, sai định dạng thì báo lỗi theo trường và ngôn ngữ', function () {
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'storefront.manage'])->create(), 'staff');

    $this->get($this->url)->assertOk()->assertInertia(fn (Assert $page) => $page->component('Storefront::Design/ThemeSettings')
        ->where('sections.0.code', 'header')
        ->where('sections.1.fields', fn ($fields) => collect($fields)->firstWhere('key', 'facebook_url')['format'] === 'url'
            && collect($fields)->firstWhere('key', 'copyright')['translatable'] === true));

    $this->put($this->url, ['settings' => ($this->settings)(['footer' => ['facebook_url' => 'javascript:alert(1)', 'contact_email' => 'khong-phai-email', 'copyright' => ['en' => str_repeat('x', 201)]]])])
        ->assertSessionHasErrors(['settings.footer.facebook_url', 'settings.footer.contact_email', 'settings.footer.copyright.en']);

    $this->put($this->url, ['settings' => [...($this->settings)(), 'la' => ['x' => 'y']]])->assertSessionHasNoErrors();
    expect(app(ThemeSettings::class)->stored())->toMatchArray(['header' => ['announcement' => ['vi' => 'Miễn phí giao hàng từ 500.000', 'en' => null], 'announcement_url' => 'https://shop.test/khuyen-mai']])
        ->and(app(ThemeSettings::class)->stored())->not->toHaveKey('la');
});

it('cần quyền storefront.manage', function () {
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access'])->create(), 'staff');

    $this->get($this->url)->assertForbidden();
    $this->put($this->url, ['settings' => ($this->settings)()])->assertForbidden();
});

it('storefront: thanh thông báo, liên hệ, mạng xã hội, thanh toán, bản quyền theo ngôn ngữ trang; trống thì rơi về bản mặc định', function () {
    T::seed(fn () => app(ThemeSettings::class)->save(($this->settings)()));

    $this->get('/')->assertOk()
        ->assertSee('Miễn phí giao hàng từ 500.000')->assertSee('href="https://shop.test/khuyen-mai"', false)
        ->assertSee('href="tel:0912345678"', false)->assertSee('12 Lê Lợi, Quận 1')
        ->assertSee('href="https://facebook.com/shop"', false)->assertSee('Chuyển khoản')->assertSee('© Cửa hàng Lan');

    // Bản en: địa chỉ và bản quyền có bản dịch; thông báo để trống → dùng bản vi.
    $this->get('/en')->assertOk()->assertSee('12 Le Loi, District 1')->assertSee('© Lan Store')->assertSee('Miễn phí giao hàng từ 500.000');
});

it('chưa cấu hình: không thanh thông báo, bản quyền mặc định', function () {
    $this->get('/')->assertOk()->assertDontSee('data-vani-announcement', false)->assertSee('© '.date('Y').' '.config('app.name'));
});

describe('theme con', function () {
    beforeEach(function () {
        $this->themes = storage_path('framework/testing/themes-'.uniqid());
        File::ensureDirectoryExists($this->themes.'/shop');
        File::ensureDirectoryExists($this->themes.'/own');
        File::link(base_path('custom/theme/vani-base'), $this->themes.'/vani-base');
        File::put($this->themes.'/shop/theme.json', json_encode(['label' => 'Shop', 'parent' => 'vani-base']));
        File::put($this->themes.'/own/theme.json', json_encode(['label' => 'Own', 'parent' => 'vani-base', 'settings' => ['footer' => ['label' => 'Chân trang', 'fields' => [
            ['key' => 'hotline', 'label' => 'Hotline', 'type' => 'string'],
            ['key' => 'Sai Khoa', 'label' => 'Khoá sai'],
            ['key' => 'loai_la', 'label' => 'Kiểu lạ', 'type' => 'image'],
        ]]]]));
        config(['vanishop.storefront.themes_path' => $this->themes]);
        app()->forgetInstance(Themes::class);
    });

    afterEach(fn () => File::deleteDirectory($this->themes));

    it('không khai báo thì dùng schema và giá trị của theme cha; tự khai báo thì thay hẳn, trường sai bị bỏ', function () {
        T::seed(fn () => app(ThemeSettings::class)->save(($this->settings)()));

        T::seed(fn () => app(Settings::class)->set('core', 'theme', 'shop'));
        expect(app(ThemeSettings::class)->values('vi')['footer']['copyright'])->toBe('© Cửa hàng Lan');

        T::seed(fn () => app(Settings::class)->set('core', 'theme', 'own'));
        expect(array_keys(app(ThemeSettings::class)->sections()))->toBe(['footer'])
            ->and(array_map(fn ($field) => $field->key, app(ThemeSettings::class)->sections()['footer']->fields))->toBe(['hotline'])
            ->and(app(ThemeSettings::class)->values('vi'))->toBe(['footer' => ['hotline' => null]]);
    });
});
