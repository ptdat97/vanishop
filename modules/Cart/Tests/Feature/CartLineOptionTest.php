<?php

use Illuminate\Support\Facades\DB;
use Modules\Cart\Contracts\CartLineOption;
use Modules\Cart\Tests\Feature\Fixtures\EngravingOption;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Hooks\HookManager;
use Modules\Extension\Contracts\Extensions;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Promotion\Application\PromotionRegistry;
use Modules\Promotion\Contracts\Data\Eligibility;
use Modules\Promotion\Contracts\Data\PromotionContext;
use Modules\Promotion\Contracts\PromotionRule;
use Modules\Promotion\Persistence\Models\PromotionRuleRecord;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';
require_once __DIR__.'/Fixtures/EngravingOption.php';

beforeEach(function () {
    ['s' => $this->s] = C::store(stock: 10);
    // `vani.cod` đang bật (plugin hệ thống) — dùng làm chủ sở hữu giả của tuỳ chọn.
    app(Extensions::class)->contribute(CartLineOption::TAG, EngravingOption::class, 'vani.cod');
    $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
    $this->cartId = $created->json('data.id');
    $this->headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
    $this->add = fn (int $quantity, ?array $options = null) => $this->postJson("/api/storefront/v1/carts/{$this->cartId}/lines", array_filter(['variant_id' => $this->s->id, 'quantity' => $quantity, 'options' => $options]), $this->headers);
});

it('cùng variant khác tuỳ chọn là hai dòng, cùng tuỳ chọn thì cộng dồn; đủ hàng xét theo tổng của variant', function () {
    ($this->add)(2)->assertOk();
    ($this->add)(1, ['vani.cod' => ['text' => ' lan ']])->assertOk();
    $lines = ($this->add)(1, ['vani.cod' => ['text' => 'LAN']])->assertOk()->json('data.lines');

    expect($lines)->toHaveCount(2)
        ->and(collect($lines)->firstWhere('options', [])['quantity'])->toBe(2)
        ->and(collect($lines)->firstWhere('options', ['vani.cod' => ['text' => 'LAN']])['quantity'])->toBe(2);

    ($this->add)(7, ['vani.cod' => ['text' => 'MAI']])->assertStatus(409)->assertJsonPath('error.code', 'cart.insufficient_stock');
});

it('tuỳ chọn của plugin không có/không bật hoặc sai → 422 có mã', function () {
    ($this->add)(1, ['vani.khong-co' => ['text' => 'x']])->assertStatus(422)->assertJsonPath('error.code', 'cart.option_unknown');
    ($this->add)(1, ['vani.cod' => ['text' => 'quá mười ký tự']])->assertStatus(422)->assertJsonPath('error.code', 'cart.option_invalid')->assertJsonPath('error.message', 'Chữ khắc tối đa 10 ký tự.');
    ($this->add)(1, ['vani.cod' => ['text' => ['mảng']]])->assertStatus(422)->assertJsonPath('error.code', 'validation.failed');
});

it('đặt hàng: tuỳ chọn chụp sang dòng đơn, giữ hàng cộng theo variant; vani.checkout.context đưa thuộc tính vào khuyến mãi', function () {
    ($this->add)(1)->assertOk();
    ($this->add)(1, ['vani.cod' => ['text' => 'LAN']])->assertOk();

    // Rule chỉ áp khi có thuộc tính ngữ cảnh do plugin bổ sung.
    app()->instance('test.needs_ref', new class implements PromotionRule
    {
        public function type(): string
        {
            return 'needs_ref';
        }

        public function label(): string
        {
            return 'Cần mã giới thiệu';
        }

        public function validateConfig(array $config): array
        {
            return [];
        }

        public function evaluate(PromotionContext $context, array $config, Eligibility $candidates): Eligibility
        {
            return isset($context->attributes['vani.cod.ref']) ? $candidates : Eligibility::none();
        }
    });
    app(Extensions::class)->tag(['test.needs_ref'], PromotionRegistry::RULES_TAG);
    $promotion = C::promotion(['requires_voucher' => false]);
    PromotionRuleRecord::query()->create(['promotion_id' => $promotion->id, 'rule_type' => 'needs_ref', 'config' => []]);
    app(HookManager::class)->onFilter('vani.checkout.context', fn (array $attributes, $request): array => [...$attributes, 'vani.cod.ref' => 'CREATOR1'], 10, 'vani.cod');

    $this->postJson("/api/storefront/v1/checkout/{$this->cartId}/orders", C::orderPayload(['expected_total' => 540_000]), [...$this->headers, 'Idempotency-Key' => 'options-order-1'])->assertCreated();

    $order = Order::query()->withoutGlobalScopes()->sole();
    expect($order->lines()->orderBy('id')->get()->map(fn ($line) => $line->meta['options'] ?? null)->all())->toBe([null, ['vani.cod' => ['text' => 'LAN']]])
        ->and((int) $order->discount_amount)->toBe(60_000)
        ->and((int) DB::table('stock_levels')->where('variant_id', $this->s->id)->value('reserved'))->toBe(2);
});
