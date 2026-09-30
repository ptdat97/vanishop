<?php

use Illuminate\Support\Facades\File;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Customer\Events\CustomerRegistered;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Tests\Fixtures\EventListeningPluginProvider;
use Modules\Extension\Tests\Fixtures\FixturePlugins;
use Modules\Payment\Events\PaymentCaptured;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/../Fixtures/FixturePlugins.php';

beforeEach(function () {
    $this->root = FixturePlugins::install(['Events' => ['id' => 'fixture.events']]);
    $this->plugins = app(PluginManager::class);
    app(CurrentContext::class)->set(ContextScope::system('test'));
    EventListeningPluginProvider::$received = [];
    EventListeningPluginProvider::$explode = false;
    $this->app->register(EventListeningPluginProvider::class);
    [$this->lumiere, $this->urbanx] = Brand::factory()->count(2)->create();
    $this->plugins->install('fixture.events');
    $this->enable = function (string $scope, ?int $id = null): void {
        $this->plugins->enable('fixture.events', $scope, $id);
        app(PluginActivation::class)->flush();
    };
});

afterEach(fn () => File::deleteDirectory($this->root));

it('onEvent: chỉ chạy cho brand bật plugin, trong phạm vi brand đó', function () {
    ($this->enable)('brand', $this->lumiere->id);

    PaymentCaptured::dispatch(1, 10, 100_000, 'cod', $this->lumiere->id);
    PaymentCaptured::dispatch(2, 11, 100_000, 'cod', $this->urbanx->id);

    expect(EventListeningPluginProvider::$received)->toBe([['event' => PaymentCaptured::class, 'brand_ids' => [$this->lumiere->id]]])
        ->and(app(CurrentContext::class)->brandIds())->toBeNull(); // phạm vi của request được khôi phục
});

it('event không mang brand (cấp Owner) chỉ tới plugin bật ở owner', function () {
    ($this->enable)('brand', $this->lumiere->id);
    CustomerRegistered::dispatch(1, '01J0000000000000000000000A', false);
    expect(EventListeningPluginProvider::$received)->toBe([]);

    ($this->enable)('owner');
    CustomerRegistered::dispatch(1, '01J0000000000000000000000A', false);
    PaymentCaptured::dispatch(2, 11, 100_000, 'cod', $this->urbanx->id);
    expect(array_column(EventListeningPluginProvider::$received, 'event'))->toBe([CustomerRegistered::class, PaymentCaptured::class]);
});

it('plugin đã tắt không nhận event; listener lỗi không làm hỏng flow', function () {
    ($this->enable)('owner');
    EventListeningPluginProvider::$explode = true;
    PaymentCaptured::dispatch(1, 10, 100_000, 'cod', $this->lumiere->id); // không ném ra ngoài

    $this->plugins->disable('fixture.events');
    app(PluginActivation::class)->flush();
    EventListeningPluginProvider::$explode = false;
    PaymentCaptured::dispatch(1, 10, 100_000, 'cod', $this->lumiere->id);

    expect(EventListeningPluginProvider::$received)->toBe([]);
});
