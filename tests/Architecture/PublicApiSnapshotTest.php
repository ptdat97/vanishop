<?php

use Modules\Extension\PluginServiceProvider;

/*
| Public API của Core (API giữa module + API cho plugin): chữ ký của mọi thứ trong Modules\*\Contracts, Modules\*\Events,
| PluginServiceProvider và class hỗ trợ trong PLUGIN_PUBLIC_SUPPORT được chụp vào public-api.snapshot (version Core).
| Phần dành cho plugin (#[PluginApi]) còn được chụp riêng vào plugin-api.snapshot với version extension_api (ADR-048) —
| xem PluginApiSnapshotTest.
|
| Đổi public API → test đỏ. Khi thay đổi là có chủ đích:
|   1. xếp loại (thêm = minor, đổi/xoá = major — với 0.x: tăng số giữa) và ghi docs/04-extension/CHANGELOG-extension.md
|   2. chạy lại với VANI_UPDATE_API_SNAPSHOT=1 để ghi snapshot mới
*/

require_once __DIR__.'/plugin-api.php';

it('public API của Core khớp snapshot (đổi API phải ghi CHANGELOG-extension + cập nhật snapshot)', function () {
    $files = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path('modules'))) as $file) {
        $path = str_replace('\\', '/', (string) $file);
        if (str_ends_with($path, '.php') && preg_match('#/modules/[^/]+/(Contracts|Events)/#', $path) === 1) {
            $files[] = $path;
        }
    }
    $files[] = str_replace('\\', '/', base_path('modules/Extension/PluginServiceProvider.php'));
    // Class hỗ trợ ngoài Contracts/Events mà plugin được dùng (R5) — danh sách đóng, cùng kỷ luật version.
    foreach (PLUGIN_PUBLIC_SUPPORT as $class) {
        $files[] = str_replace('\\', '/', base_path('modules/'.str_replace('\\', '/', substr($class, strlen('Modules\\'))).'.php'));
    }
    sort($files);

    $lines = [];
    $modulesPath = rtrim(str_replace('\\', '/', base_path('modules')), '/').'/';
    foreach ($files as $path) {
        $class = 'Modules\\'.str_replace(['/', '.php'], ['\\', ''], substr($path, strlen($modulesPath)));
        if (class_exists($class) || interface_exists($class) || enum_exists($class)) {
            array_push($lines, ...publicApiLines(new ReflectionClass($class)));
        }
    }

    // Helper toàn cục cho plugin/theme (ADR-036, ADR-037) cũng là public API.
    // Helper nằm ở module sở hữu contract (vòng 0 không biết thương mại, R29): modules/<Module>/helpers.php.
    $helperFiles = array_map(fn (string $file): string => str_replace('\\', '/', $file), glob(base_path('modules/*/helpers.php')) ?: []);
    $helpers = array_filter(get_defined_functions()['user'], fn (string $function): bool => in_array(str_replace('\\', '/', (string) (new ReflectionFunction($function))->getFileName()), $helperFiles, true));
    sort($helpers);
    foreach ($helpers as $function) {
        $reflection = new ReflectionFunction($function);
        $return = $reflection->getReturnType() === null ? '' : ': '.$reflection->getReturnType();
        $lines[] = "function {$reflection->getName()}(".implode(', ', array_map('publicApiSignature', $reflection->getParameters())).")$return";
    }

    $snapshot = __DIR__.'/public-api.snapshot';
    $current = 'VaniShop '.config('vanishop.version')."\n".implode("\n", $lines)."\n";
    if (getenv('VANI_UPDATE_API_SNAPSHOT') === '1' || ! is_file($snapshot)) {
        file_put_contents($snapshot, $current);
    }

    expect($current)->toBe(file_get_contents($snapshot));
});
