<?php

use Illuminate\Support\Facades\Log;
use Modules\Extension\Application\Hooks\HookManager;
use Modules\Extension\Application\Hooks\HookRegistry;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Domain\Hooks\HookDefinition;
use Modules\Extension\Domain\Hooks\HookNotDeclared;
use Modules\Extension\Domain\Hooks\HookNotPublic;
use Modules\Extension\Domain\Hooks\HookReturnTypeMismatch;
use Modules\Extension\Domain\Hooks\HookType;
use Modules\Extension\Facades\Hook;
use TorMorten\Eventy\Events;

beforeEach(function () {
    $registry = app(HookRegistry::class);
    $registry->declare(new HookDefinition('test.filter', HookType::Filter, true, '0.1'));
    $registry->declare(new HookDefinition('test.validate', HookType::Validate, true, '0.1'));
    $registry->declare(new HookDefinition('test.slot', HookType::Slot, true, '0.1'));
    $registry->declare(new HookDefinition('test.action', HookType::Action, true, '0.1'));
    $registry->declare(new HookDefinition('test.internal', HookType::Filter, false, '0.1'));
});

it('filter chạy theo priority và truyền tham số bổ sung', function () {
    Hook::onFilter('test.filter', fn (string $v, string $suffix) => $v.'-b'.$suffix, 20);
    Hook::onFilter('test.filter', fn (string $v) => $v.'-a', 5);

    expect(Hook::filter('test.filter', 'x', '!'))->toBe('x-a-b!');
});

it('action gọi listener với tham số', function () {
    $received = null;
    Hook::onAction('test.action', function (string $value) use (&$received) {
        $received = $value;
    });

    Hook::action('test.action', 'done');

    expect($received)->toBe('done');
});

it('validate gộp lỗi từ mọi listener', function () {
    Hook::onValidate('test.validate', fn (int $total) => $total > 100 ? ['quá hạn mức'] : []);
    Hook::onValidate('test.validate', fn () => ['thiếu SĐT']);

    expect(Hook::collect('test.validate', 150))->toBe(['quá hạn mức', 'thiếu SĐT']);
});

it('slot bỏ qua listener lỗi mà không làm hỏng trang', function () {
    Log::spy();
    Hook::onSlot('test.slot', fn () => throw new RuntimeException('plugin lỗi'));
    Hook::onSlot('test.slot', fn () => ['title' => 'OK']);

    expect(Hook::slot('test.slot'))->toBe([['title' => 'OK']]);
    Log::shouldHaveReceived('error')->once();
});

it('chế độ strict chặn hook chưa khai báo', function () {
    Hook::filter('test.not-declared', 1);
})->throws(HookNotDeclared::class);

it('plugin không được nghe hook internal', function () {
    Hook::onFilter('test.internal', fn ($v) => $v, pluginId: 'vani.some-plugin');
})->throws(HookNotPublic::class);

it('listener của plugin không chạy khi plugin chưa bật', function () {
    Hook::onFilter('test.filter', fn (string $v) => $v.'-plugin', pluginId: 'vani.not-enabled');

    expect(Hook::filter('test.filter', 'x'))->toBe('x');
});

it('filter trả sai kiểu: strict → HookReturnTypeMismatch; production → bỏ kết quả sai, giữ giá trị', function () {
    Hook::onFilter('test.filter', fn (array $value): string => 'không phải mảng');

    expect(fn () => Hook::filter('test.filter', ['a' => 1]))->toThrow(HookReturnTypeMismatch::class);

    $production = new HookManager(new Events, app(HookRegistry::class), fn () => app(PluginActivation::class), false);
    $production->onFilter('test.filter', fn (array $value): string => 'không phải mảng');
    $production->onFilter('test.filter', fn (array $value): array => [...$value, 'b' => 2], 20);
    Log::spy();

    expect($production->filter('test.filter', ['a' => 1]))->toBe(['a' => 1, 'b' => 2]);
    Log::shouldHaveReceived('warning')->withArgs(fn (string $message): bool => str_contains($message, 'sai kiểu'))->once();

    // Object: kết quả phải cùng lớp (hoặc lớp con) với đầu vào.
    $objects = new HookManager(new Events, app(HookRegistry::class), fn () => app(PluginActivation::class), false);
    $objects->onFilter('test.filter', fn (object $value): object => new stdClass);
    expect($objects->filter('test.filter', new ArrayObject))->toBeInstanceOf(ArrayObject::class);
});

it('đo hook_duration_ms theo hook × plugin; listener chậm hơn ngưỡng bị ghi cảnh báo', function () {
    $manager = new HookManager(new Events, app(HookRegistry::class), fn () => app(PluginActivation::class), true, slowMs: 5);
    $manager->onAction('test.action', fn () => usleep(10_000));
    $manager->onFilter('test.filter', fn (string $value): string => $value);
    Log::spy();

    $manager->action('test.action');
    $manager->filter('test.filter', 'x');
    $manager->filter('test.filter', 'y');

    $timings = collect($manager->timings())->keyBy('hook');
    expect($timings['test.action']['calls'])->toBe(1)
        ->and($timings['test.action']['max_ms'])->toBeGreaterThan(5.0)
        ->and($timings['test.filter']['calls'])->toBe(2)
        ->and($timings['test.filter']['plugin'])->toBe('core');
    Log::shouldHaveReceived('warning')->withArgs(fn (string $message, array $context): bool => $context['hook'] === 'test.action' && $context['hook_duration_ms'] > 5)->once();
});
