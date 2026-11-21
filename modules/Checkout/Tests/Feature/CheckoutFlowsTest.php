<?php

use Modules\Checkout\Application\CheckoutFlows;
use Modules\Checkout\Application\TotalsPipeline;
use Modules\Checkout\Contracts\Data\TotalsContext;
use Modules\Checkout\Contracts\TotalsCalculator;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Hooks\HookRegistry;
use Modules\Extension\Contracts\Data\FlowStep;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Domain\Hooks\HookType;
use Modules\Extension\Facades\Hook;
use Modules\Shared\Support\ExternalCalls;

require_once __DIR__.'/CheckoutTestHelpers.php';

final class FlowTestCalculator implements TotalsCalculator
{
    public static int $priority = 350;

    public function code(): string
    {
        return 'flow_test';
    }

    public function priority(): int
    {
        return self::$priority;
    }

    public function calculate(TotalsContext $context): TotalsContext
    {
        return $context;
    }
}

/*
| `vani:flows` (học từ step list của Lunar 2.x): mô tả luồng phải khớp lần chạy thật — thứ tự hook đúng như khi xem trước
| và đặt hàng, mọi hook trong mô tả đều thật sự được gọi.
*/

/**
 * Nghe mọi hook của luồng, ghi theo thứ tự gọi lần đầu: tên => có đang trong transaction không.
 *
 * @param  list<FlowStep>  $steps
 * @return ArrayObject<string, bool>
 */
function recordFlowHooks(array $steps): ArrayObject
{
    $fired = new ArrayObject;
    $record = function (string $name) use ($fired): void {
        if (! $fired->offsetExists($name)) {
            $fired[$name] = ExternalCalls::insideTransaction();
        }
    };
    foreach (array_unique(array_filter(array_map(fn (FlowStep $step): ?string => $step->hook, $steps))) as $name) {
        match (app(HookRegistry::class)->get($name)->type) {
            HookType::Action => Hook::onAction($name, fn () => $record($name)),
            HookType::Validate => Hook::onValidate($name, function () use ($record, $name): array {
                $record($name);

                return [];
            }),
            default => Hook::onFilter($name, function (mixed $value) use ($record, $name): mixed {
                $record($name);

                return $value;
            }),
        };
    }

    return $fired;
}

/**
 * @param  list<FlowStep>  $steps
 * @return array<string, bool> hook => trong transaction (lần xuất hiện đầu)
 */
function flowHooks(array $steps): array
{
    $hooks = [];
    foreach ($steps as $step) {
        if ($step->hook !== null && ! array_key_exists($step->hook, $hooks)) {
            $hooks[$step->hook] = $step->transaction;
        }
    }

    return $hooks;
}

beforeEach(function () {
    FlowTestCalculator::$priority = 350;
    ['s' => $this->variant] = C::store();
    $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
    $this->headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
    $this->cart = $created->json('data.id');
    $this->postJson("/api/storefront/v1/carts/{$this->cart}/lines", ['variant_id' => $this->variant->id, 'quantity' => 1], $this->headers)->assertOk();
});

it('xem trước checkout gọi hook đúng thứ tự trong mô tả luồng, ngoài transaction', function () {
    $steps = app(CheckoutFlows::class)->quote();
    $fired = recordFlowHooks($steps);

    $this->postJson("/api/storefront/v1/checkout/{$this->cart}/quote", C::orderPayload(), $this->headers)->assertOk();

    expect((array) $fired)->toBe(flowHooks($steps));
});

it('đặt hàng gọi hook đúng thứ tự, đúng phía của transaction như mô tả luồng', function () {
    $steps = app(CheckoutFlows::class)->placeOrder();
    $fired = recordFlowHooks($steps);

    $this->postJson("/api/storefront/v1/checkout/{$this->cart}/orders", C::orderPayload(['expected_total' => 330_000]), [...$this->headers, 'Idempotency-Key' => 'flow-0001'])->assertCreated();

    expect((array) $fired)->toBe(flowHooks($steps));
});

it('vani:flows in các bước của luồng và báo thành công khi khai báo hook khớp', function () {
    $this->artisan('vani:flows', ['flow' => CheckoutFlows::PLACE_ORDER])
        ->expectsOutputToContain('hook vani.order.after_create')
        ->expectsOutputToContain('TotalsCalculator guard')
        ->assertSuccessful();

    $this->artisan('vani:flows', ['flow' => 'khong.co'])->assertFailed();
});

it('calculator của plugin hiện đúng chỗ trong luồng; ngoài dải 300–399 thì doctor cảnh báo', function () {
    app(Extensions::class)->contribute(TotalsCalculator::TAG, FlowTestCalculator::class, 'vani.cod');
    $position = function (string $name): int {
        $names = array_map(fn (FlowStep $step): string => $step->name, app(CheckoutFlows::class)->quote());

        return (int) array_search($name, $names, true);
    };

    expect($position('TotalsCalculator flow_test'))->toBe($position('vani.service.promotion.result') + 1)
        ->and(app(TotalsPipeline::class)->misplacedPluginCalculators())->toBe([]);

    FlowTestCalculator::$priority = 950;
    expect($position('TotalsCalculator flow_test'))->toBe($position('TotalsCalculator guard') + 1)
        ->and(app(TotalsPipeline::class)->misplacedPluginCalculators())->toBe(['vani.cod:flow_test (950)']);
});
