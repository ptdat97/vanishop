<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Identity\Contracts\PermissionRegistry;
use Modules\Notification\Application\Mail\MailSettings;
use Modules\Tenancy\Contracts\Data\SettingDefinition;
use Modules\Tenancy\Contracts\Settings;

/*
| Quyền Admin → Cài đặt: `settings.manage` = toàn bộ; `settings.<nhóm>` chỉ nhóm đó; `settings.plugin.<id>` chỉ plugin
| đó; ô bí mật cần thêm `settings.secrets`.
*/

beforeEach(function () {
    app(Settings::class)->define(new SettingDefinition('test.plugin', 'pixel_id', 'Pixel'));
    app(Settings::class)->define(new SettingDefinition('test.plugin', 'api_key', 'Khoá', 'secret'));
    app(PermissionRegistry::class)->register('settings.plugin.test.plugin', 'extension::texts.permissions.plugin_settings', params: ['plugin' => 'test.plugin']);
    $this->as = fn (array $permissions) => $this->actingAs(T::staff(['admin.access', ...$permissions]), 'staff');
});

it('chỉ settings.seo: thấy và sửa đúng nhóm SEO; nhóm khác, plugin, menu theo quyền', function () {
    ($this->as)(['settings.seo']);

    $this->get('/admin/settings')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('system', fn ($groups) => collect($groups)->pluck('code')->all() === ['system:seo'])->where('plugins', []));
    $this->get('/admin/settings/core')->assertRedirect(route('admin.settings.system', 'seo'));
    $this->get('/admin/settings/system/seo')->assertOk()->assertInertia(fn (Assert $page) => $page->where('nav.system', fn ($links) => count($links) === 1));
    $this->get('/admin/settings/system/sales')->assertForbidden();
    $this->get('/admin/settings/test.plugin')->assertForbidden();

    $this->put('/admin/settings', ['namespace' => 'core', 'values' => ['storefront.popular_searches' => "đầm\náo"]])->assertSessionHasNoErrors();
    // Gửi kèm một khoá của nhóm khác → cả lượt bị từ chối, không lưu gì.
    $this->put('/admin/settings', ['namespace' => 'core', 'values' => ['storefront.popular_searches' => 'váy', 'tax.calculator' => 'zero_tax']])->assertForbidden();
    expect(app(Settings::class)->explicit('core'))->not->toHaveKey('tax.calculator');

    $menu = fn (): array => collect($this->get('/admin')->viewData('page')['props']['navigation'])->pluck('key')->all();
    expect($menu())->toContain('settings');

    ($this->as)([]);
    $this->get('/admin/settings')->assertForbidden();
    expect($menu())->not->toContain('settings')->and($menu())->not->toBeEmpty();
});

it('quyền cài đặt của plugin: sửa ô thường được; ô bí mật cần settings.secrets (giao diện khoá ô)', function () {
    ($this->as)(['settings.plugin.test.plugin']);

    $this->get('/admin/settings')->assertInertia(fn (Assert $page) => $page->where('system', [])->where('plugins.0.namespace', 'test.plugin'));
    $this->get('/admin/settings/test.plugin')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('fields', fn ($fields) => collect($fields)->firstWhere('key', 'api_key')['editable'] === false && collect($fields)->firstWhere('key', 'pixel_id')['editable'] === true));
    $this->put('/admin/settings', ['namespace' => 'test.plugin', 'values' => ['pixel_id' => '123']])->assertSessionHasNoErrors();
    $this->put('/admin/settings', ['namespace' => 'test.plugin', 'values' => ['api_key' => 'bi-mat']])->assertForbidden();
    // Ô bí mật để trống = giữ nguyên: form gửi cả ô khoá vẫn lưu được ô thường.
    $this->put('/admin/settings', ['namespace' => 'test.plugin', 'values' => ['pixel_id' => '456', 'api_key' => '']])->assertSessionHasNoErrors();
    expect(app(Settings::class)->get('test.plugin', 'pixel_id'))->toBe('456')->and(app(Settings::class)->get('test.plugin', 'api_key'))->toBeNull();

    ($this->as)(['settings.plugin.test.plugin', 'settings.secrets']);
    $this->put('/admin/settings', ['namespace' => 'test.plugin', 'values' => ['api_key' => 'bi-mat']])->assertSessionHasNoErrors();
    expect(app(Settings::class)->get('test.plugin', 'api_key'))->toBe('bi-mat');
});

it('Email gửi đi: settings.mail sửa máy chủ và gửi thử được, mật khẩu SMTP cần settings.secrets; settings.manage làm được hết', function () {
    ($this->as)(['settings.mail']);
    $this->put('/admin/settings', ['namespace' => 'core', 'values' => [MailSettings::HOST => 'smtp.zoho.com']])->assertSessionHasNoErrors();
    $this->put('/admin/settings', ['namespace' => 'core', 'values' => [MailSettings::PASSWORD => 'x']])->assertForbidden();
    $this->post('/admin/notifications/mail/test')->assertRedirect();

    ($this->as)(['settings.seo']);
    $this->post('/admin/notifications/mail/test')->assertForbidden();

    ($this->as)(['settings.manage']);
    $this->put('/admin/settings', ['namespace' => 'core', 'values' => [MailSettings::PASSWORD => 'x', 'tax.calculator' => null]])->assertSessionHasNoErrors();
    $this->get('/admin/settings/test.plugin')->assertOk();
});
