<?php

use Modules\Extension\Domain\Plugin\InvalidManifest;
use Modules\Extension\Domain\Plugin\PluginManifest;

$valid = [
    'id' => 'vani.vietqr', 'name' => ['vi' => 'VietQR', 'en' => 'VietQR'], 'version' => '1.0.0',
    'kind' => 'payment_gateway', 'provider' => 'Plugin\\VietQr\\VietQrServiceProvider',
    'requires' => ['vanishop' => '^0.1', 'plugins' => ['vani.base' => '^1.0']],
    'scopes' => ['legal_entity', 'brand'],
];

it('đọc manifest hợp lệ', function () use ($valid) {
    $manifest = PluginManifest::fromArray($valid, '/p');

    expect($manifest->id)->toBe('vani.vietqr')
        ->and($manifest->requiresCore)->toBe('^0.1')
        ->and($manifest->requiresPlugins)->toBe(['vani.base' => '^1.0'])
        ->and($manifest->displayName('en'))->toBe('VietQR')
        ->and($manifest->conflicts)->toBe([]);
});

it('từ chối manifest sai', function (array $override, string $reason) use ($valid) {
    expect(fn () => PluginManifest::fromArray(array_merge($valid, $override), '/p'))
        ->toThrow(InvalidManifest::class, $reason);
})->with([
    'id sai dạng' => [['id' => 'VietQR'], 'vendor.name'],
    'version không semver' => [['version' => '1.0'], 'SemVer'],
    'thiếu requires.vanishop' => [['requires' => ['plugins' => []]], 'requires.vanishop'],
]);

it('từ chối manifest thiếu trường bắt buộc', function () use ($valid) {
    $data = $valid;
    unset($data['provider']);

    PluginManifest::fromArray($data, '/p');
})->throws(InvalidManifest::class, 'provider');
