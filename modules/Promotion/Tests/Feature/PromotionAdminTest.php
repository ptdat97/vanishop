<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Extension\Contracts\Extensions;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Promotion\Application\PromotionRegistry;
use Modules\Promotion\Contracts\Data\Eligibility;
use Modules\Promotion\Contracts\Data\PromotionContext;
use Modules\Promotion\Contracts\PromotionRule;
use Modules\Promotion\Persistence\Models\Promotion;
use Modules\Promotion\Persistence\Models\Voucher;

require_once __DIR__.'/../../../Catalog/Tests/Feature/CatalogTestHelpers.php';

/**
 * Rule giả cho test Admin: minh hoạ một rule do plugin cung cấp (đăng ký qua Extensions::tag).
 */
final class MinQuantityTestRule implements PromotionRule
{
    public function type(): string
    {
        return 'test.min_quantity';
    }

    public function label(): string
    {
        return 'Số lượng tối thiểu';
    }

    public function validateConfig(array $config): array
    {
        $quantity = $config['quantity'] ?? null;

        return is_int($quantity) && $quantity > 0 ? [] : ['Số lượng phải là số nguyên dương.'];
    }

    public function evaluate(PromotionContext $context, array $config, Eligibility $candidates): Eligibility
    {
        $quantity = array_sum(array_map(fn (int $key): int => $context->line($key)?->quantity ?? 0, $candidates->keys));

        return $quantity >= (int) ($config['quantity'] ?? 0) ? $candidates : Eligibility::none();
    }
}

beforeEach(function () {
    $this->brand = Brand::factory()->create(['slug' => 'lumiere']);
    $this->other = Brand::factory()->create(['slug' => 'urbanx']);
    $this->actingAs(T::staff(['admin.access', 'promotion.view', 'promotion.manage']), 'staff');
    $this->base = '/admin/promotion/promotions';
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

it('lưu và xoá điều kiện của plugin, từ chối loại chưa đăng ký hoặc config sai', function () {
    app(Extensions::class)->tag([MinQuantityTestRule::class], PromotionRegistry::RULES_TAG);

    $this->post($this->base, promotionPayload(['rules' => [['type' => 'test.min_quantity', 'config' => ['quantity' => 3]]]]))->assertSessionHasNoErrors();

    $promotion = T::seed(fn () => Promotion::query()->sole());
    expect($promotion->rules()->pluck('rule_type')->all())->toBe(['test.min_quantity'])
        ->and($promotion->rules()->sole()->config)->toBe(['quantity' => 3]);

    $this->put("{$this->base}/{$promotion->id}", promotionPayload(['lock_version' => 0, 'rules' => []]))->assertSessionHasNoErrors();
    expect($promotion->rules()->count())->toBe(0);

    $this->post($this->base, promotionPayload(['rules' => [['type' => 'test.unknown', 'config' => []]]]))->assertSessionHasErrors('rules.0.type');
    $this->post($this->base, promotionPayload(['rules' => [['type' => 'test.min_quantity', 'config' => ['quantity' => 0]]]]))->assertSessionHasErrors('rules.0.config');
});

it('kiểm tra quyền', function () {

    $this->actingAs(T::staff(['admin.access', 'promotion.view']), 'staff');
    $this->get($this->base)->assertOk();
    $this->post($this->base, promotionPayload())->assertForbidden();
});
