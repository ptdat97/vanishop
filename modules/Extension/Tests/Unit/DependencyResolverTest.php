<?php

use Modules\Extension\Domain\Plugin\CircularDependency;
use Modules\Extension\Domain\Plugin\DependencyProblem;
use Modules\Extension\Domain\Plugin\DependencyResolver;
use Modules\Extension\Domain\Plugin\PluginManifest;

function manifest(string $id, string $core = '^0.1', array $plugins = [], array $conflicts = [], string $version = '1.0.0'): PluginManifest
{
    return PluginManifest::fromArray([
        'id' => $id, 'name' => $id, 'version' => $version, 'kind' => 'feature',
        'provider' => 'X', 'requires' => ['vanishop' => $core, 'plugins' => $plugins], 'conflicts' => $conflicts,
    ], '/tmp/'.$id);
}

function codes(array $problems): array
{
    return array_map(fn (DependencyProblem $p) => $p->code, $problems);
}

it('không có vấn đề khi tương thích', function () {
    $available = ['v.a' => manifest('v.a')];

    expect((new DependencyResolver)->problemsFor('v.a', $available, [], '0.1.3'))->toBe([]);
});

it('phát hiện Core không tương thích', function () {
    $available = ['v.a' => manifest('v.a', '^1.0')];

    expect(codes((new DependencyResolver)->problemsFor('v.a', $available, [], '0.1.0')))->toBe(['incompatible_core']);
});

it('phát hiện phụ thuộc thiếu, sai phiên bản, chưa bật', function () {
    $available = [
        'v.einvoice' => manifest('v.einvoice', version: '1.2.0'),
        'v.misa' => manifest('v.misa', plugins: ['v.einvoice' => '^2.0']),
        'v.vnpt' => manifest('v.vnpt', plugins: ['v.einvoice' => '^1.0']),
        'v.orphan' => manifest('v.orphan', plugins: ['v.missing' => '^1.0']),
    ];
    $resolver = new DependencyResolver;

    expect(codes($resolver->problemsFor('v.misa', $available, ['v.einvoice'], '0.1.0')))->toBe(['incompatible_dependency'])
        ->and(codes($resolver->problemsFor('v.vnpt', $available, [], '0.1.0')))->toBe(['inactive_dependency'])
        ->and(codes($resolver->problemsFor('v.vnpt', $available, ['v.einvoice'], '0.1.0')))->toBe([])
        ->and(codes($resolver->problemsFor('v.orphan', $available, [], '0.1.0')))->toBe(['missing_dependency']);
});

it('phát hiện xung đột theo cả hai chiều', function () {
    $available = ['v.a' => manifest('v.a', conflicts: ['v.b']), 'v.b' => manifest('v.b')];
    $resolver = new DependencyResolver;

    expect(codes($resolver->problemsFor('v.a', $available, ['v.b'], '0.1.0')))->toBe(['conflict'])
        ->and(codes($resolver->problemsFor('v.b', $available, ['v.a'], '0.1.0')))->toBe(['conflict']);
});

it('sắp thứ tự nạp: phụ thuộc trước', function () {
    $manifests = [
        'v.c' => manifest('v.c', plugins: ['v.b' => '*']),
        'v.b' => manifest('v.b', plugins: ['v.a' => '*']),
        'v.a' => manifest('v.a'),
        'v.z' => manifest('v.z'),
    ];

    expect((new DependencyResolver)->loadOrder($manifests))->toBe(['v.a', 'v.b', 'v.c', 'v.z']);
});

it('phát hiện phụ thuộc vòng', function () {
    (new DependencyResolver)->loadOrder([
        'v.a' => manifest('v.a', plugins: ['v.b' => '*']),
        'v.b' => manifest('v.b', plugins: ['v.a' => '*']),
    ]);
})->throws(CircularDependency::class);

it('liệt kê plugin đang hoạt động phụ thuộc vào một plugin', function () {
    $available = ['v.base' => manifest('v.base'), 'v.child' => manifest('v.child', plugins: ['v.base' => '*'])];

    expect((new DependencyResolver)->dependentsOf('v.base', $available, ['v.base', 'v.child']))->toBe(['v.child']);
});
