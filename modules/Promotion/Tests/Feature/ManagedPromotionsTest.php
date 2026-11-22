<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Contracts\Extensions;
use Modules\Promotion\Application\PromotionRegistry;
use Modules\Promotion\Contracts\ContextualPromotionAction;
use Modules\Promotion\Contracts\Data\ManagedPromotion;
use Modules\Promotion\Contracts\Data\PromotionContext;
use Modules\Promotion\Contracts\Data\PromotionLine;
use Modules\Promotion\Contracts\ManagedPromotions;
use Modules\Promotion\Contracts\PromotionEngine;
use Modules\Promotion\Persistence\Models\Promotion;
use Modules\Shared\Domain\Money\Money;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/** Action theo ngữ cảnh: giảm 10.000đ cho mỗi đơn vị, chỉ thấy được qua số lượng của dòng. */
final class PerUnitTestAction implements ContextualPromotionAction
{
    public function type(): string
    {
        return 'per_unit_test';
    }

    public function label(): string
    {
        return 'Giảm theo đơn vị';
    }

    public function validateConfig(array $config): array
    {
        return [];
    }

    public function apply(array $remaining, array $config, string $currencyCode): array
    {
        return array_map(fn (Money $money): Money => Money::zero($currencyCode), $remaining);
    }

    public function applyInContext(PromotionContext $context, array $remaining, array $config): array
    {
        $discounts = [];
        foreach ($remaining as $key => $money) {
            $discounts[$key] = Money::of(10_000 * ($context->line($key)?->quantity ?? 0), $context->currencyCode);
        }

        return $discounts;
    }
}

beforeEach(function () {
    ['brand' => $this->brand, 's' => $this->s] = C::store();
    app(Extensions::class)->tag([PerUnitTestAction::class], PromotionRegistry::ACTIONS_TAG);
    $this->managed = app(ManagedPromotions::class);
});

it('action theo ngữ cảnh nhận số lượng dòng cả khi đánh giá lẫn khi kiểm tra lại', function () {
    $id = $this->managed->sync('vani.test', 'k1', new ManagedPromotion('Theo đơn vị', true, 'per_unit_test', []));
    $context = fn (int $quantity) => new PromotionContext(null, 'VND', [
        new PromotionLine($this->s->id, $this->brand->id, $this->s->style_id, $quantity, Money::vnd(300_000), Money::vnd(300_000 * $quantity)),
    ], [], now()->getTimestamp());

    expect(app(PromotionEngine::class)->evaluate($context(3))->discountByLine())->toBe([$this->s->id => 30_000])
        ->and(app(PromotionEngine::class)->recheck($context(1), [$id])[$id][$this->s->id]->amount)->toBe(10_000);
});

it('plugin đồng bộ, cập nhật và gỡ khuyến mãi theo khoá; Admin chỉ xem', function () {
    $id = $this->managed->sync('vani.test', 'k1', new ManagedPromotion('Combo A', true, 'percent_off', ['basis_points' => 1000], startsAt: now()->getTimestamp()));
    expect($this->managed->sync('vani.test', 'k1', new ManagedPromotion('Combo A mới', false, 'percent_off', ['basis_points' => 1500])))->toBe($id)
        ->and(Promotion::query()->findOrFail($id)->only(['name', 'status', 'managed_by', 'managed_key']))
        ->toBe(['name' => 'Combo A mới', 'status' => 'inactive', 'managed_by' => 'vani.test', 'managed_key' => 'k1'])
        ->and($this->managed->ids('vani.test'))->toBe(['k1' => $id])
        ->and(fn () => $this->managed->sync('vani.test', 'k2', new ManagedPromotion('Sai', true, 'khong_co', [])))->toThrow(ValidationException::class);

    $this->actingAs(T::staff(['admin.access', 'promotion.view', 'promotion.manage']), 'staff');
    $this->get(route('admin.promotion.promotions.index'))->assertInertia(fn (Assert $page) => $page->where('promotions.0.managed', true));
    $this->get(route('admin.promotion.promotions.edit', $id))->assertInertia(fn (Assert $page) => $page->where('promotion.managed.owner', 'vani.test'));
    $this->delete(route('admin.promotion.promotions.destroy', $id))->assertSessionHasErrors('promotion');
    expect(Promotion::query()->whereKey($id)->exists())->toBeTrue();

    DB::table('promotion_usages')->insert(['promotion_id' => $id, 'order_id' => 1, 'discount_amount' => 1, 'currency_code' => 'VND', 'status' => 'applied', 'created_at' => now(), 'updated_at' => now()]);
    $this->managed->remove('vani.test', 'k1');
    expect(Promotion::query()->findOrFail($id)->status)->toBe('inactive');
    $other = $this->managed->sync('vani.test', 'k3', new ManagedPromotion('Combo B', true, 'percent_off', ['basis_points' => 500]));
    $this->managed->remove('vani.test', 'k3');
    expect(Promotion::query()->whereKey($other)->exists())->toBeFalse();
});
