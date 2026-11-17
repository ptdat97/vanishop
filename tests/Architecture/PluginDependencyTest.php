<?php

/*
| Plugin dùng plugin khác (ADR-030 §4.H): chỉ qua Contracts/Events/Testing của plugin đó, và phải khai báo plugin đó
| trong requires.plugins (resolver sắp thứ tự nạp, chặn tắt plugin đang được phụ thuộc).
*/

use Composer\Semver\Semver;

it('plugin chỉ dùng Contracts/Events/Testing của plugin đã khai báo trong requires.plugins', function () {
    $root = realpath(__DIR__.'/../../custom/plugin');
    $plugins = [];
    foreach (glob("{$root}/*/vanishop.json") as $file) {
        $manifest = json_decode((string) file_get_contents($file), true);
        $plugins[basename(dirname($file))] = ['id' => $manifest['id'], 'requires' => array_keys((array) ($manifest['requires']['plugins'] ?? []))];
    }

    $offenders = [];
    foreach ($plugins as $directory => $plugin) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator("{$root}/{$directory}", FilesystemIterator::SKIP_DOTS)) as $file) {
            // Test của plugin được bật plugin khác để kiểm tra phối hợp (vd. ZNS dự phòng sang SMS).
            if ($file->getExtension() !== 'php' || str_contains($file->getPathname(), '/Tests/')) {
                continue;
            }
            preg_match_all('/Plugin\\\\(\w+)\\\\(\w+)/', (string) file_get_contents($file->getPathname()), $matches, PREG_SET_ORDER);
            foreach ($matches as [, $other, $layer]) {
                if ($other === $directory || ! isset($plugins[$other])) {
                    continue;
                }
                if (! in_array($plugins[$other]['id'], $plugin['requires'], true) || ! in_array($layer, ['Contracts', 'Events', 'Testing'], true)) {
                    $offenders[] = "{$plugin['id']} dùng Plugin\\{$other}\\{$layer}";
                }
            }
        }
    }

    expect(array_values(array_unique($offenders)))->toBe([]);
});

it('mọi plugin trong repo tương thích với API cho plugin hiện tại (requires.extension_api, ADR-048) và Core nếu có khai', function () {
    $extensionApi = (string) config('vanishop.extension_api');
    $coreVersion = (string) config('vanishop.version');
    $offenders = [];
    foreach (glob(__DIR__.'/../../custom/plugin/*/vanishop.json') as $file) {
        $manifest = json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
        $constraint = (string) ($manifest['requires']['extension_api'] ?? '');
        if ($constraint === '' || ! Semver::satisfies($extensionApi, $constraint)) {
            $offenders[] = "{$manifest['id']} cần API mở rộng [{$constraint}], hiện tại là {$extensionApi}";
        }
        $core = $manifest['requires']['vanishop'] ?? null;
        if ($core !== null && ! Semver::satisfies($coreVersion, (string) $core)) {
            $offenders[] = "{$manifest['id']} cần VaniShop [{$core}], Core là {$coreVersion}";
        }
    }

    expect($offenders)->toBe([]);
});
