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

it('Admin → Cấu hình: form (lựa chọn lấy từ extension point), lưu/xoá có audit, secret không lộ', function () {
    app(Extensions::class)->tag([ZeroTax::class], TaxCalculator::TAG);
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'settings.manage'])->create(), 'staff');

    $this->get('/admin/settings?namespace=core')->assertInertia(fn (Assert $page) => $page->component('Extension::Settings/Index')
        ->where('namespace', 'core')
        ->where('fields', fn ($fields) => collect($fields)->firstWhere('key', 'tax.calculator')['options'] === ['none' => 'none', 'vn_vat_inclusive' => 'vn_vat_inclusive', 'zero_tax' => 'zero_tax']));

    $this->put('/admin/settings', ['namespace' => 'core', 'values' => ['tax.calculator' => 'zero_tax', 'returns.policy' => '']])->assertSessionHasNoErrors();
    expect(app(Settings::class)->get('core', 'tax.calculator'))->toBe('zero_tax')
        ->and(AuditLog::query()->where('action', 'settings.updated')->count())->toBe(1);

    $this->put('/admin/settings', ['namespace' => 'core', 'values' => ['tax.calculator' => 'khong_ton_tai']])->assertSessionHasErrors('values.tax.calculator');
    $this->put('/admin/settings', ['namespace' => 'core', 'values' => ['khong.co' => 'x']])->assertSessionHasErrors();

    $this->put('/admin/settings', ['namespace' => 'test', 'values' => ['api_key' => 'bi-mat']])->assertSessionHasNoErrors();
    $this->get('/admin/settings?namespace=test')->assertInertia(fn (Assert $page) => $page
        ->where('fields', fn ($fields) => collect($fields)->firstWhere('key', 'api_key')['value'] === null && collect($fields)->firstWhere('key', 'api_key')['is_set'] === true));
    $this->put('/admin/settings', ['namespace' => 'test', 'values' => ['api_key' => '']])->assertSessionHasNoErrors();
    expect(app(Settings::class)->get('test', 'api_key'))->toBe('bi-mat'); // secret rỗng = giữ nguyên

    $this->put('/admin/settings', ['namespace' => 'core', 'values' => ['tax.calculator' => null]])->assertSessionHasNoErrors();
    expect(app(Settings::class)->explicit('core'))->toBe([]);

    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access'])->create(), 'staff');
    $this->get('/admin/settings')->assertForbidden();
});
