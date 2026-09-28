<?php

use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Contracts\CollectionDirectory;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Promotion\Application\PromotionRegistry;
use Modules\Promotion\Contracts\Data\Eligibility;
use Modules\Promotion\Contracts\Data\PromotionContext;
use Modules\Promotion\Contracts\Data\PromotionLine;
use Modules\Shared\Context\Actor;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Domain\Money\Money;
use Plugin\PromotionRules\Domain\Rules\FirstOrderOnlyRule;
use Plugin\PromotionRules\Domain\Rules\InCollectionsRule;
use Plugin\PromotionRules\Domain\Rules\MinOrderSubtotalRule;
use Plugin\PromotionRules\Domain\Rules\MinQuantityRule;
use Plugin\PromotionRules\PromotionRulesServiceProvider;

beforeEach(function () {
    $this->brand = Brand::factory()->create(['slug' => 'brand-promo']);
    $this->otherBrand = Brand::factory()->create(['slug' => 'brand-other']);
});

function installPromotionRules(string $scopeType = 'owner', ?int $scopeId = null): void
{
    $context = app(CurrentContext::class);
    $context->runAs(ContextScope::system('test'), function () use ($scopeType, $scopeId) {
        $plugins = app(PluginManager::class);
        $plugins->install('vani.promotion-rules');
        $plugins->enable('vani.promotion-rules', $scopeType, $scopeId);
    });

    app()->register(PromotionRulesServiceProvider::class);
    app(PluginActivation::class)->flush();
}

function makeContext(array $lines, ?int $customerId = 1, int $channelId = 1): PromotionContext
{
    return new PromotionContext(
        channelId: $channelId,
        customerId: $customerId,
        currencyCode: 'VND',
        lines: $lines,
        voucherCodes: [],
        now: time(),
    );
}

function makeLine(int $key, int $brandId, int $styleId, int $quantity, int $unitPrice): PromotionLine
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
    $orders->shouldReceive('customerHasPlacedOrder')->with(100, 1)->andReturnFalse();
    $orders->shouldReceive('customerHasPlacedOrder')->with(200, 1)->andReturnTrue();

    $rule = new FirstOrderOnlyRule($orders);
    expect($rule->type())->toBe('first_order_only')
        ->and($rule->validateConfig([]))->toBe([])
        ->and($rule->validateConfig(['scope' => 'invalid']))->not->toBeEmpty();

    $lines = [makeLine(key: 1, brandId: 1, styleId: 10, quantity: 1, unitPrice: 100_000)];

    expect($rule->evaluate(makeContext($lines, customerId: 100), [], new Eligibility([1]))->keys)->toBe([1])
        ->and($rule->evaluate(makeContext($lines, customerId: 200), [], new Eligibility([1]))->keys)->toBe([])
        ->and($rule->evaluate(makeContext($lines, customerId: null), [], new Eligibility([1]))->keys)->toBe([]);
});

it('tích hợp: plugin đăng ký rule vào PromotionRegistry và chỉ hiển thị ở brand được bật', function () {
    installPromotionRules('brand', $this->brand->id);

    $registry = app(PromotionRegistry::class);
    $context = app(CurrentContext::class);

    $typesInScope = $context->runAs(new ContextScope(Actor::guest(), brandIds: [$this->brand->id]), function () use ($registry) {
        app(PluginActivation::class)->flush();

        return array_map(fn ($r) => $r->type(), array_values($registry->rules()));
    });

    expect($typesInScope)->toContain('min_order_subtotal', 'min_quantity', 'in_collections', 'first_order_only');

    $typesOutOfScope = $context->runAs(new ContextScope(Actor::guest(), brandIds: [$this->otherBrand->id]), function () use ($registry) {
        app(PluginActivation::class)->flush();

        return array_map(fn ($r) => $r->type(), array_values($registry->rules()));
    });

    expect($typesOutOfScope)->not->toContain('min_order_subtotal', 'min_quantity', 'in_collections', 'first_order_only');
});
