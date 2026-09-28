<?php

use Illuminate\Support\Facades\Log;
use Modules\Extension\Application\Hooks\HookRegistry;
use Modules\Extension\Domain\Hooks\HookDefinition;
use Modules\Extension\Domain\Hooks\HookNotDeclared;
use Modules\Extension\Domain\Hooks\HookNotPublic;
use Modules\Extension\Domain\Hooks\HookType;
use Modules\Extension\Facades\Hook;

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
