<?php

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Contracts\TaxCalculator;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Contracts\Extensions;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Tenancy\Contracts\Data\SettingDefinition;
use Modules\Tenancy\Contracts\Data\SettingsScope;
use Modules\Tenancy\Contracts\Settings;
use Modules\Tenancy\Tests\Feature\Fixtures\ZeroTax;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    $this->settings = app(Settings::class);
    $this->settings->define(new SettingDefinition('test', 'greeting', 'Lời chào', default: 'xin chào', scopes: ['owner', 'brand', 'channel']));
    $this->settings->define(new SettingDefinition('test', 'api_key', 'Khoá', 'secret'));
});

it('kế thừa kênh → brand → pháp nhân → owner → mặc định của định nghĩa', function () {
    $scope = new SettingsScope(channelId: 7, brandId: 3, legalEntityId: 2);
    expect($this->settings->get('test', 'greeting', $scope))->toBe('xin chào')
        ->and($this->settings->get('test', 'khong_co', $scope, 'dự phòng'))->toBe('dự phòng');

    $this->settings->set('test', 'greeting', 'owner', 'owner');
    $this->settings->set('test', 'greeting', 'pháp nhân', 'legal_entity', 2);
    $this->settings->set('test', 'greeting', 'brand', 'brand', 3);
    expect($this->settings->get('test', 'greeting', $scope))->toBe('brand')
        ->and($this->settings->get('test', 'greeting', SettingsScope::brand(9, 2)))->toBe('pháp nhân')
        ->and($this->settings->get('test', 'greeting', SettingsScope::brand(9)))->toBe('owner');

    $this->settings->set('test', 'greeting', 'kênh', 'channel', 7);
    expect($this->settings->get('test', 'greeting', $scope))->toBe('kênh');

    $this->settings->forget('test', 'greeting', 'channel', 7);
    expect($this->settings->get('test', 'greeting', $scope))->toBe('brand')
        ->and($this->settings->explicit('test', 'brand', 3))->toBe(['greeting' => 'brand']);
});

it('secret được mã hoá khi lưu', function () {
    $this->settings->set('test', 'api_key', 'bi-mat-123', 'owner');

    $raw = DB::table('settings')->where('key', 'api_key')->first();
    expect($raw->encrypted)->toBeTruthy()
        ->and($raw->value)->not->toContain('bi-mat-123')
        ->and(app(Settings::class)->get('test', 'api_key', SettingsScope::owner()))->toBe('bi-mat-123');
});

describe('chọn strategy theo brand', function () {
    beforeEach(function () {
        app(Extensions::class)->tag([ZeroTax::class], TaxCalculator::TAG);
        ['brand' => $this->lumiere, 's' => $this->s] = C::store();
        $this->quoteTax = function (): int {
            $headers = ['X-Vani-Channel' => 'web-lumiere'];
            $created = $this->postJson('/api/storefront/v1/carts', [], $headers);
            $headers['X-Vani-Cart-Token'] = $created->json('meta.token');
            $this->postJson("/api/storefront/v1/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();

            return (int) $this->postJson("/api/storefront/v1/checkout/{$created->json('data.id')}/quote", [], $headers)->assertOk()->json('data.tax_included.amount');
        };
    });

    it('brand đặt TaxCalculator riêng; brand khác giữ mặc định', function () {
        expect(($this->quoteTax)())->toBeGreaterThan(0);

        T::seed(fn () => app(Settings::class)->set('core', 'tax.calculator', 'zero_tax', 'brand', $this->lumiere->id));
        expect(($this->quoteTax)())->toBe(0);

        $other = Brand::factory()->create();
        expect(app(Settings::class)->get('core', 'tax.calculator', SettingsScope::brand($other->id)))->toBe('vn_vat_inclusive');
    });

    it('cấu hình trỏ tới implementation không có hiệu lực (plugin tắt/gõ sai) → dùng mặc định, không làm hỏng checkout', function () {
        T::seed(fn () => app(Settings::class)->set('core', 'tax.calculator', 'plugin_da_tat', 'brand', $this->lumiere->id));

        expect(($this->quoteTax)())->toBeGreaterThan(0);
    });
});

it('Admin → Cấu hình: form theo phạm vi (lựa chọn lấy từ extension point), lưu/xoá override có audit, secret không lộ', function () {
    app(Extensions::class)->tag([ZeroTax::class], TaxCalculator::TAG);
    $brand = Brand::factory()->create();
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'settings.manage'])->create(), 'staff');

    $this->get("/admin/settings?namespace=core&scope=brand:{$brand->id}")->assertInertia(fn (Assert $page) => $page->component('Extension::Settings/Index')
        ->where('namespace', 'core')
        ->where('fields', fn ($fields) => collect($fields)->firstWhere('key', 'tax.calculator')['options'] === ['vn_vat_inclusive' => 'vn_vat_inclusive', 'zero_tax' => 'zero_tax']));

    $this->put('/admin/settings', ['namespace' => 'core', 'scope' => "brand:{$brand->id}", 'values' => ['tax.calculator' => 'zero_tax', 'returns.policy' => '']])->assertSessionHasNoErrors();
    expect(app(Settings::class)->get('core', 'tax.calculator', SettingsScope::brand($brand->id)))->toBe('zero_tax')
        ->and(AuditLog::query()->where('action', 'settings.updated')->count())->toBe(1);

    $this->put('/admin/settings', ['namespace' => 'core', 'scope' => "brand:{$brand->id}", 'values' => ['tax.calculator' => 'khong_ton_tai']])->assertSessionHasErrors('values.tax.calculator');
    $this->put('/admin/settings', ['namespace' => 'core', 'scope' => 'owner', 'values' => ['khong.co' => 'x']])->assertSessionHasErrors();

    $this->put('/admin/settings', ['namespace' => 'test', 'scope' => 'owner', 'values' => ['api_key' => 'bi-mat']])->assertSessionHasNoErrors();
    $this->get('/admin/settings?namespace=test&scope=owner')->assertInertia(fn (Assert $page) => $page
        ->where('fields', fn ($fields) => collect($fields)->firstWhere('key', 'api_key')['value'] === null && collect($fields)->firstWhere('key', 'api_key')['is_set'] === true));
    $this->put('/admin/settings', ['namespace' => 'test', 'scope' => 'owner', 'values' => ['api_key' => '']])->assertSessionHasNoErrors();
    expect(app(Settings::class)->get('test', 'api_key', SettingsScope::owner()))->toBe('bi-mat'); // secret rỗng = giữ nguyên

    $this->put('/admin/settings', ['namespace' => 'core', 'scope' => "brand:{$brand->id}", 'values' => ['tax.calculator' => null]])->assertSessionHasNoErrors();
    expect(app(Settings::class)->explicit('core', 'brand', $brand->id))->toBe([]);

    $this->actingAs(T::staffFor($brand, ['admin.access', 'settings.manage']), 'staff');
    $this->get('/admin/settings')->assertForbidden();
});
