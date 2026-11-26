<?php

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Contracts\TaxCalculator;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Contracts\Extensions;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Tenancy\Contracts\Data\SettingDefinition;
use Modules\Tenancy\Contracts\Settings;
use Modules\Tenancy\Tests\Feature\Fixtures\ZeroTax;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    $this->settings = app(Settings::class);
    $this->settings->define(new SettingDefinition('test', 'greeting', 'Lời chào', default: 'xin chào'));
    $this->settings->define(new SettingDefinition('test', 'api_key', 'Khoá', 'secret'));
});

it('giá trị đã đặt → mặc định của định nghĩa → $default; forget quay về mặc định', function () {
    expect($this->settings->get('test', 'greeting'))->toBe('xin chào')
        ->and($this->settings->get('test', 'khong_co', 'dự phòng'))->toBe('dự phòng');

    $this->settings->set('test', 'greeting', 'chào cửa hàng');
    expect($this->settings->get('test', 'greeting'))->toBe('chào cửa hàng')
        ->and($this->settings->explicit('test'))->toBe(['greeting' => 'chào cửa hàng']);

    $this->settings->forget('test', 'greeting');
    expect($this->settings->get('test', 'greeting'))->toBe('xin chào')
        ->and($this->settings->explicit('test'))->toBe([]);
});

it('secret được mã hoá khi lưu', function () {
    $this->settings->set('test', 'api_key', 'bi-mat-123');

    $raw = DB::table('settings')->where('key', 'api_key')->first();
    expect($raw->encrypted)->toBeTruthy()
        ->and($raw->value)->not->toContain('bi-mat-123')
        ->and(app(Settings::class)->get('test', 'api_key'))->toBe('bi-mat-123');
});

describe('chọn strategy qua cấu hình', function () {
    beforeEach(function () {
        app(Extensions::class)->tag([ZeroTax::class], TaxCalculator::TAG);
        ['s' => $this->s] = C::store();
        $this->quoteTax = function (): int {
            $headers = [];
            $created = $this->postJson('/api/storefront/v1/carts', [], $headers);
            $headers['X-Vani-Cart-Token'] = $created->json('meta.token');
            $this->postJson("/api/storefront/v1/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();

            return (int) $this->postJson("/api/storefront/v1/checkout/{$created->json('data.id')}/quote", [], $headers)->assertOk()->json('data.tax_included.amount');
        };
    });

    it('đặt TaxCalculator khác mặc định', function () {
        expect(($this->quoteTax)())->toBeGreaterThan(0);

        T::seed(fn () => app(Settings::class)->set('core', 'tax.calculator', 'zero_tax'));
        expect(($this->quoteTax)())->toBe(0);
    });

    it('cấu hình trỏ tới implementation không có hiệu lực (plugin tắt/gõ sai) → dùng mặc định, không làm hỏng checkout', function () {
        T::seed(fn () => app(Settings::class)->set('core', 'tax.calculator', 'plugin_da_tat'));

        expect(($this->quoteTax)())->toBeGreaterThan(0);
    });
});

it('Admin → Cài đặt: form (lựa chọn lấy từ extension point), lưu/xoá có audit, secret không lộ', function () {
    app(Extensions::class)->tag([ZeroTax::class], TaxCalculator::TAG);
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'settings.manage'])->create(), 'staff');

    $this->get('/admin/settings')->assertInertia(fn (Assert $page) => $page->component('Extension::Settings/Index')
        ->where('system', fn ($groups) => collect($groups)->pluck('code')->all() === ['system:store', 'system:appearance', 'system:seo', 'system:sales', 'system:inventory', 'system:orders', 'system:mail', 'system:other']
            && collect($groups)->firstWhere('code', 'system:sales')['url'] === route('admin.settings.system', 'sales')
            && collect($groups)->firstWhere('code', 'system:sales')['count'] === 9
            && ! array_key_exists('fields', collect($groups)->first()))
        ->where('plugins', fn ($plugins) => ! collect($plugins)->contains('namespace', 'core')));

    // `core` không còn là một trang dài: mở nhóm đầu; mỗi nhóm chỉ có cấu hình của nhóm đó.
    $this->get('/admin/settings/core')->assertRedirect(route('admin.settings.system', 'store'));
    $this->get('/admin/settings/system/sales')->assertInertia(fn (Assert $page) => $page->component('Extension::Settings/Show')
        ->where('namespace', 'core')
        ->where('active', 'system:sales')
        ->where('nav.system.3.code', 'system:sales')
        ->where('fields', fn ($fields) => collect($fields)->pluck('key')->sort()->values()->all() === ['cart.abandoned_after_minutes', 'cart.max_line_quantity', 'cart.max_lines', 'cart.ttl_days', 'payment.capture_on', 'pricing.max_discount_percent', 'pricing.strategy', 'storefront.guest_checkout', 'tax.calculator']
            && collect($fields)->firstWhere('key', 'tax.calculator')['options'] === ['none' => 'none', 'vn_vat_inclusive' => 'vn_vat_inclusive', 'zero_tax' => 'zero_tax']));
    $this->get('/admin/settings/system/khong-co')->assertNotFound();
    $this->get('/admin/settings/system/other')->assertOk(); // lưu giữ dữ liệu (db-refactor G3) nằm ở nhóm Khác

    $this->put('/admin/settings', ['namespace' => 'core', 'values' => ['tax.calculator' => 'zero_tax', 'returns.policy' => '']])->assertSessionHasNoErrors();
    expect(app(Settings::class)->get('core', 'tax.calculator'))->toBe('zero_tax')
        ->and(AuditLog::query()->where('action', 'settings.updated')->count())->toBe(1);
    $this->get('/admin/settings')->assertInertia(fn (Assert $page) => $page->where('system.3.customized', 1)->where('system.5.customized', 0));

    $this->put('/admin/settings', ['namespace' => 'core', 'values' => ['tax.calculator' => 'khong_ton_tai']])->assertSessionHasErrors('values.tax.calculator');
    $this->put('/admin/settings', ['namespace' => 'core', 'values' => ['khong.co' => 'x']])->assertSessionHasErrors();

    $this->put('/admin/settings', ['namespace' => 'test', 'values' => ['api_key' => 'bi-mat']])->assertSessionHasNoErrors();
    $this->get('/admin/settings')->assertInertia(fn (Assert $page) => $page->where('plugins', fn ($plugins) => collect($plugins)->firstWhere('namespace', 'test')['customized'] === 1));
    $this->get('/admin/settings/test')->assertInertia(fn (Assert $page) => $page
        ->where('active', 'plugin:test')
        ->where('fields', fn ($fields) => collect($fields)->firstWhere('key', 'api_key')['value'] === null
            && collect($fields)->firstWhere('key', 'api_key')['is_set'] === true));
    $this->get('/admin/settings/unknown')->assertNotFound();
    $this->put('/admin/settings', ['namespace' => 'test', 'values' => ['api_key' => '']])->assertSessionHasNoErrors();
    expect(app(Settings::class)->get('test', 'api_key'))->toBe('bi-mat'); // secret rỗng = giữ nguyên

    $this->put('/admin/settings', ['namespace' => 'core', 'values' => ['tax.calculator' => null]])->assertSessionHasNoErrors();
    expect(app(Settings::class)->explicit('core'))->toBe([]);

    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access'])->create(), 'staff');
    $this->get('/admin/settings')->assertForbidden();
});

it('cấu hình hệ thống không khai nhóm → nhóm "Khác" (cuối danh sách)', function () {
    app(Settings::class)->define(new SettingDefinition('core', 'test.ungrouped', 'Chưa xếp nhóm'));
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'settings.manage'])->create(), 'staff');

    $this->get('/admin/settings')->assertInertia(fn (Assert $page) => $page->where('system', fn ($groups) => collect($groups)->last()['code'] === 'system:other'
        && in_array('Chưa xếp nhóm', collect($groups)->last()['labels'], true)));
    $this->get('/admin/settings/system/other')->assertOk()->assertInertia(fn (Assert $page) => $page->where('fields', fn ($fields) => collect($fields)->contains('key', 'test.ungrouped')));
});

it('rule validate thêm của cấu hình (0.3.43): sai → lỗi theo trường, không lưu; rỗng = đặt lại không bị kiểm tra', function () {
    app(Settings::class)->define(new SettingDefinition('test', 'color', 'Màu thanh', rules: ['regex:/^#[0-9a-f]{6}$/i']));
    app(Settings::class)->define(new SettingDefinition('test', 'message', 'Nội dung', rules: ['regex:/\{amount\}/']));
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'settings.manage'])->create(), 'staff');

    $this->put('/admin/settings', ['namespace' => 'test', 'values' => ['color' => 'đỏ']])->assertSessionHasErrors('values.color');
    $this->put('/admin/settings', ['namespace' => 'test', 'values' => ['message' => 'Thêm tiền để miễn phí']])->assertSessionHasErrors('values.message');
    expect(app(Settings::class)->explicit('test'))->toBe([]);

    $this->put('/admin/settings', ['namespace' => 'test', 'values' => ['color' => '#93C5FD', 'message' => 'Thêm {amount} nữa']])->assertSessionHasNoErrors();
    expect(app(Settings::class)->get('test', 'color'))->toBe('#93C5FD');
    $this->put('/admin/settings', ['namespace' => 'test', 'values' => ['color' => '']])->assertSessionHasNoErrors();
    expect(app(Settings::class)->get('test', 'color'))->toBeNull();
});
