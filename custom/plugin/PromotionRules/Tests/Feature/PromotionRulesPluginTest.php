<?php

use Modules\Catalog\Contracts\CollectionDirectory;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Promotion\Application\PromotionRegistry;
use Modules\Promotion\Contracts\Data\Eligibility;
use Modules\Promotion\Contracts\Data\PromotionContext;
use Modules\Promotion\Contracts\Data\PromotionLine;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Domain\Money\Money;
use Plugin\PromotionRules\Domain\Rules\FirstOrderOnlyRule;
use Plugin\PromotionRules\Domain\Rules\InBrandsRule;
use Plugin\PromotionRules\Domain\Rules\InCollectionsRule;
use Plugin\PromotionRules\Domain\Rules\MinOrderSubtotalRule;
use Plugin\PromotionRules\Domain\Rules\MinQuantityRule;
use Plugin\PromotionRules\PromotionRulesServiceProvider;

beforeEach(function () {
    $this->brand = Brand::factory()->create(['slug' => 'brand-promo']);
    $this->otherBrand = Brand::factory()->create(['slug' => 'brand-other']);
});

function installPromotionRules(bool $enable = true): void
{
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () use ($enable) {
        $plugins = app(PluginManager::class);
        $plugins->install('vani.promotion-rules');
        if ($enable) {
            $plugins->enable('vani.promotion-rules');
        }
    });

    app()->register(PromotionRulesServiceProvider::class);
    app(PluginActivation::class)->flush();
}

function makeContext(array $lines, ?int $customerId = 1): PromotionContext
{
    return new PromotionContext(
        customerId: $customerId,
        currencyCode: 'VND',
        lines: $lines,
        voucherCodes: [],
        now: time(),
    );
}

function makeLine(int $key, ?int $brandId, int $styleId, int $quantity, int $unitPrice): PromotionLine
{
    $unit = Money::vnd($unitPrice);

    return new PromotionLine(
        key: $key,
        brandId: $brandId,
        styleId: $styleId,
        quantity: $quantity,
        unitPrice: $unit,
        subtotal: $unit->multiply($quantity),
    );
}

it('MinOrderSubtotalRule: kiểm tra ngưỡng tổng tiền đơn', function () {
    $rule = new MinOrderSubtotalRule;
    expect($rule->type())->toBe('min_order_subtotal')
        ->and($rule->validateConfig(['min_subtotal' => 200_000]))->toBe([])
        ->and($rule->validateConfig(['min_subtotal' => -10]))->not->toBeEmpty()
        ->and($rule->validateConfig([]))->not->toBeEmpty();

    $lines = [makeLine(key: 1, brandId: 1, styleId: 10, quantity: 2, unitPrice: 100_000)];
    $ctx = makeContext($lines);

    expect($rule->evaluate($ctx, ['min_subtotal' => 200_000], new Eligibility([1]))->keys)->toBe([1])
        ->and($rule->evaluate($ctx, ['min_subtotal' => 300_000], new Eligibility([1]))->keys)->toBe([]);
});

it('MinQuantityRule: kiểm tra ngưỡng tổng số lượng', function () {
    $rule = new MinQuantityRule;
    expect($rule->type())->toBe('min_quantity')
        ->and($rule->validateConfig(['min_quantity' => 3]))->toBe([])
        ->and($rule->validateConfig(['min_quantity' => 0]))->not->toBeEmpty();

    $lines = [
        makeLine(key: 1, brandId: 1, styleId: 10, quantity: 2, unitPrice: 50_000),
        makeLine(key: 2, brandId: 1, styleId: 11, quantity: 1, unitPrice: 50_000),
    ];
    $ctx = makeContext($lines);

    expect($rule->evaluate($ctx, ['min_quantity' => 3], new Eligibility([1, 2]))->keys)->toBe([1, 2])
        ->and($rule->evaluate($ctx, ['min_quantity' => 4], new Eligibility([1, 2]))->keys)->toBe([]);
});

it('InCollectionsRule: lọc các dòng thuộc bộ sưu tập', function () {
    $collections = Mockery::mock(CollectionDirectory::class);
    $collections->shouldReceive('slugsForStyles')
        ->with([10, 20])
        ->andReturn([
            10 => ['he-2026', 'sale'],
            20 => ['thu-2026'],
        ]);

    $rule = new InCollectionsRule($collections);
    expect($rule->type())->toBe('in_collections')
        ->and($rule->validateConfig(['slugs' => ['he-2026']]))->toBe([])
        ->and($rule->validateConfig(['slugs' => []]))->not->toBeEmpty()
        ->and($rule->validateConfig([]))->not->toBeEmpty();

    $lines = [
        makeLine(key: 1, brandId: 1, styleId: 10, quantity: 1, unitPrice: 100_000),
        makeLine(key: 2, brandId: 1, styleId: 20, quantity: 1, unitPrice: 100_000),
    ];
    $ctx = makeContext($lines);

    expect($rule->evaluate($ctx, ['slugs' => ['sale']], new Eligibility([1, 2]))->keys)->toBe([1])
        ->and($rule->evaluate($ctx, ['slugs' => ['dong-2026']], new Eligibility([1, 2]))->keys)->toBe([]);
});

it('FirstOrderOnlyRule: từ chối khách đã từng đặt đơn và khách vãng lai', function () {
    $orders = Mockery::mock(OrderReader::class);
    $orders->shouldReceive('customerHasPlacedOrder')->with(100)->andReturnFalse();
    $orders->shouldReceive('customerHasPlacedOrder')->with(200)->andReturnTrue();

    $rule = new FirstOrderOnlyRule($orders);
    expect($rule->type())->toBe('first_order_only')
        ->and($rule->validateConfig([]))->toBe([])
        ->and($rule->validateConfig(['scope' => 'invalid']))->not->toBeEmpty();

    $lines = [makeLine(key: 1, brandId: 1, styleId: 10, quantity: 1, unitPrice: 100_000)];

    expect($rule->evaluate(makeContext($lines, customerId: 100), [], new Eligibility([1]))->keys)->toBe([1])
        ->and($rule->evaluate(makeContext($lines, customerId: 200), [], new Eligibility([1]))->keys)->toBe([])
        ->and($rule->evaluate(makeContext($lines, customerId: null), [], new Eligibility([1]))->keys)->toBe([]);
});

it('InBrandsRule: lọc các dòng thuộc thương hiệu chỉ định (theo slug), bỏ qua brand ẩn và dòng không brand', function () {
    $rule = app(InBrandsRule::class);
    expect($rule->type())->toBe('in_brands')
        ->and($rule->validateConfig(['slugs' => ['brand-promo']]))->toBe([])
        ->and($rule->validateConfig(['slugs' => []]))->not->toBeEmpty()
        ->and($rule->validateConfig(['slugs' => ['']]))->not->toBeEmpty();

    $ctx = makeContext([
        makeLine(key: 1, brandId: $this->brand->id, styleId: 10, quantity: 1, unitPrice: 100_000),
        makeLine(key: 2, brandId: $this->otherBrand->id, styleId: 11, quantity: 1, unitPrice: 100_000),
        makeLine(key: 3, brandId: null, styleId: 12, quantity: 1, unitPrice: 100_000),
    ]);

    expect($rule->evaluate($ctx, ['slugs' => ['brand-promo']], new Eligibility([1, 2, 3]))->keys)->toBe([1])
        ->and($rule->evaluate($ctx, ['slugs' => ['brand-promo', 'brand-other']], new Eligibility([2, 3]))->keys)->toBe([2])
        ->and($rule->evaluate($ctx, ['slugs' => ['khong-co']], new Eligibility([1, 2, 3]))->keys)->toBe([]);

    $this->brand->update(['status' => 'hidden']);
    expect($rule->evaluate($ctx, ['slugs' => ['brand-promo']], new Eligibility([1, 2, 3]))->keys)->toBe([]);
});

it('tích hợp: plugin đăng ký rule vào PromotionRegistry chỉ khi được bật', function () {
    $types = function (): array {
        app(PluginActivation::class)->flush();

        return array_map(fn ($r) => $r->type(), array_values(app(PromotionRegistry::class)->rules()));
    };

    installPromotionRules(enable: false);
    expect($types())->not->toContain('min_order_subtotal', 'min_quantity', 'in_collections', 'first_order_only', 'in_brands');

    app(CurrentContext::class)->runAs(ContextScope::system('test'), fn () => app(PluginManager::class)->enable('vani.promotion-rules'));
    expect($types())->toContain('min_order_subtotal', 'min_quantity', 'in_collections', 'first_order_only', 'in_brands');
});
