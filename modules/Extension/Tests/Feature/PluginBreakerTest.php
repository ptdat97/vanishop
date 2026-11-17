<?php

use Illuminate\Support\Facades\File;
use Modules\Extension\Application\Hooks\HookManager;
use Modules\Extension\Application\Hooks\HookRegistry;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Application\Plugins\PluginBreaker;
use Modules\Extension\Application\Plugins\PluginDoctor;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Domain\Hooks\HookDefinition;
use Modules\Extension\Domain\Hooks\HookType;
use Modules\Extension\Tests\Fixtures\FixturePlugins;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/../Fixtures/FixturePlugins.php';

/*
| Củng cố kiến trúc K3 (P6): circuit breaker theo plugin cho slot UI và filter `on_error: skip` — plugin lỗi liên tục bị
| bỏ qua tạm thời, doctor báo `breaker_open`; hook giao dịch (validate) không bị bỏ qua.
*/

beforeEach(function () {
    $registry = app(HookRegistry::class);
    $registry->declare(new HookDefinition('test.breaker.slot', HookType::Slot, true, '0.1'));
    $registry->declare(new HookDefinition('test.breaker.read', HookType::Filter, true, '0.1', onError: 'skip'));
    $registry->declare(new HookDefinition('test.breaker.validate', HookType::Validate, true, '0.1'));
    $this->root = FixturePlugins::install(['Greeting' => ['id' => 'fixture.greeting']]);
    app(CurrentContext::class)->set(ContextScope::system('test'));
    app(PluginManager::class)->install('fixture.greeting');
    app(PluginManager::class)->enable('fixture.greeting');
    app(PluginActivation::class)->flush();
    $this->hooks = app(HookManager::class);
});

afterEach(fn () => File::deleteDirectory($this->root));

it('slot lỗi 5 lần/phút → bỏ qua plugin trong thời gian nghỉ (cả filter đọc của plugin); Core vẫn chạy; doctor báo; hết nghỉ thì thử lại', function () {
    $this->useArrayCache(); // travel() qua thời gian nghỉ
    $calls = 0;
    $this->hooks->onSlot('test.breaker.slot', function () use (&$calls): string {
        $calls++;
        throw new RuntimeException('slot hỏng');
    }, pluginId: 'fixture.greeting');
    $this->hooks->onSlot('test.breaker.slot', fn (): string => 'core');
    $this->hooks->onFilter('test.breaker.read', fn (string $value): string => $value.'+plugin', pluginId: 'fixture.greeting');

    foreach (range(1, 5) as $ignored) {
        expect($this->hooks->slot('test.breaker.slot'))->toBe(['core']);
    }
    expect($this->hooks->slot('test.breaker.slot'))->toBe(['core'])
        ->and($calls)->toBe(5)
        ->and($this->hooks->filter('test.breaker.read', 'x'))->toBe('x')
        ->and(collect(app(PluginDoctor::class)->diagnose())->where('plugin', 'fixture.greeting')->pluck('code')->all())->toContain('breaker_open');

    $this->travel(301)->seconds();
    expect($this->hooks->filter('test.breaker.read', 'x'))->toBe('x+plugin');
});

it('hook giao dịch (validate) không bị bỏ qua khi mạch mở', function () {
    foreach (range(1, 5) as $ignored) {
        app(PluginBreaker::class)->recordFailure('fixture.greeting', 'test');
    }
    $this->hooks->onValidate('test.breaker.validate', fn (): array => ['chặn'], pluginId: 'fixture.greeting');

    expect($this->hooks->collect('test.breaker.validate'))->toBe(['chặn']);
});
