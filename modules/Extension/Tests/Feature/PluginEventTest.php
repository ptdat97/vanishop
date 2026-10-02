<?php

use Illuminate\Support\Facades\File;
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
    $this->plugins->install('fixture.events');
    $this->enable = function (): void {
        $this->plugins->enable('fixture.events');
        app(PluginActivation::class)->flush();
    };
});

afterEach(fn () => File::deleteDirectory($this->root));

it('onEvent: plugin bật nhận event trong phạm vi system, phạm vi của request được khôi phục', function () {
    PaymentCaptured::dispatch(1, 10, 100_000, 'cod');
    expect(EventListeningPluginProvider::$received)->toBe([]);

    ($this->enable)();
    $before = app(CurrentContext::class)->scope();
    CustomerRegistered::dispatch(1, '01J0000000000000000000000A', false);
    PaymentCaptured::dispatch(2, 11, 100_000, 'cod');

    expect(EventListeningPluginProvider::$received)->toBe([
        ['event' => CustomerRegistered::class, 'actor' => 'system'],
        ['event' => PaymentCaptured::class, 'actor' => 'system'],
    ])->and(app(CurrentContext::class)->scope())->toBe($before);
});

it('plugin đã tắt không nhận event; listener lỗi không làm hỏng flow', function () {
    ($this->enable)();
    EventListeningPluginProvider::$explode = true;
    PaymentCaptured::dispatch(1, 10, 100_000, 'cod'); // không ném ra ngoài

    $this->plugins->disable('fixture.events');
    app(PluginActivation::class)->flush();
    EventListeningPluginProvider::$explode = false;
    PaymentCaptured::dispatch(1, 10, 100_000, 'cod');

    expect(EventListeningPluginProvider::$received)->toBe([]);
});
