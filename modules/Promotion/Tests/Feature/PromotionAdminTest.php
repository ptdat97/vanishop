<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Promotion\Persistence\Models\Promotion;
use Modules\Promotion\Persistence\Models\Voucher;

require_once __DIR__.'/../../../Catalog/Tests/Feature/CatalogTestHelpers.php';

beforeEach(function () {
    $this->brand = Brand::factory()->create(['slug' => 'lumiere']);
    $this->other = Brand::factory()->create(['slug' => 'urbanx']);
    $this->actingAs(T::staffFor($this->brand, ['admin.access', 'promotion.view', 'promotion.manage']), 'staff');
    $this->base = '/admin/promotion/lumiere/promotions';
});

function promotionPayload(array $overrides = []): array
{
    return array_replace(['name' => 'Giảm 15%', 'status' => 'active', 'priority' => 0, 'stacking' => 'combinable', 'requires_voucher' => true,
        'action_type' => 'percent_off', 'action_config' => ['basis_points' => 1500], 'usage_limit' => null, 'budget_amount' => null], $overrides);
}

it('tạo, sửa khuyến mãi với optimistic lock và audit', function () {
    $this->post($this->base, promotionPayload())->assertSessionHasNoErrors();
    $promotion = T::seed(fn () => Promotion::query()->sole());

    $this->get("{$this->base}/{$promotion->id}/edit")->assertInertia(fn (Assert $page) => $page->component('Promotion::Promotions/Form')
        ->where('promotion.action_config.basis_points', 1500)->has('actions', 2));

    $this->put("{$this->base}/{$promotion->id}", promotionPayload(['action_type' => 'amount_off', 'action_config' => ['amount' => 50_000], 'lock_version' => 0]))->assertSessionHasNoErrors();
    $this->put("{$this->base}/{$promotion->id}", promotionPayload(['lock_version' => 0]))->assertSessionHasErrors('lock_version');

    expect($promotion->fresh()->action_config)->toBe(['amount' => 50_000])
        ->and(AuditLog::query()->where('action', 'like', 'promotion.%')->count())->toBe(2);
});

it('từ chối cấu hình giảm giá sai và loại chưa đăng ký', function () {
    $this->post($this->base, promotionPayload(['action_config' => ['basis_points' => 0]]))->assertSessionHasErrors('action_config');
    $this->post($this->base, promotionPayload(['action_type' => 'khong_co']))->assertSessionHasErrors('action_config');
});

it('tạo voucher cụ thể và sinh hàng loạt, mã duy nhất', function () {
    $this->post($this->base, promotionPayload());
    $promotion = T::seed(fn () => Promotion::query()->sole());

    $this->post("{$this->base}/{$promotion->id}/vouchers", ['code' => 'sale10', 'usage_limit' => 100])->assertSessionHasNoErrors();
    $this->post("{$this->base}/{$promotion->id}/vouchers", ['prefix' => 'LM', 'count' => 25, 'usage_limit' => 1])->assertSessionHasNoErrors();
    $this->post("{$this->base}/{$promotion->id}/vouchers", ['code' => 'SALE10'])->assertSessionHasErrors('code');

    $codes = Voucher::query()->pluck('code');
    expect($codes)->toHaveCount(26)->and($codes)->toContain('SALE10')
        ->and($codes->filter(fn ($code) => str_starts_with($code, 'LM') && strlen($code) === 10))->toHaveCount(25);
});

it('cô lập brand và quyền', function () {
    $foreign = T::seed(fn () => Promotion::query()->create(['brand_id' => $this->other->id, 'name' => 'X', 'action_type' => 'percent_off', 'action_config' => ['basis_points' => 1000]]));

    $this->get("{$this->base}/{$foreign->id}/edit")->assertNotFound();
    $this->get('/admin/promotion/urbanx/promotions')->assertNotFound();

    $this->actingAs(T::staffFor($this->brand, ['admin.access', 'promotion.view']), 'staff');
    $this->get($this->base)->assertOk();
    $this->post($this->base, promotionPayload())->assertForbidden();
});
